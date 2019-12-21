<?php

declare(strict_types=1);

namespace Laminas\Transfer\Fixture;

use Laminas\Transfer\Repository;

use function array_unique;
use function basename;
use function explode;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function implode;
use function in_array;
use function is_dir;
use function preg_match;
use function preg_replace;
use function str_replace;
use function strlen;
use function strpos;
use function strstr;
use function strtr;
use function trim;
use function unlink;
use function usort;

class QAConfigFixture extends AbstractFixture
{
    /** @var string[] */
    private $files = [
        '.gitattributes',
        '.gitignore',
        '.travis.yml',
        'phpcs.xm*',
        '*.neon',
        'phpunit.xm*',
        '*/phpunit.xm*',
        'Dockerfile',
        'Makefile',
        'Vagrantfile',
    ];

    public function process(Repository $repository) : void
    {
        foreach ($this->files as $fileName) {
            foreach ($repository->files($fileName) as $file) {
                if ($fileName === 'Makefile') {
                    unlink($file);
                    continue;
                }
                $this->replace($repository, $file);
            }
        }
    }

    private function getDeps(string $section) : ?string
    {
        if (! preg_match('/^\s*- DEPS=(?P<deps>lowest|locked|latest)/m', $section, $match)) {
            return null;
        }

        return $match['deps'];
    }

    private function getPhpVersion(string $section) : ?string
    {
        if (! preg_match('/^\s*- php: [\'"]?(?P<version>[\d.]+)[\'"]?/m', $section, $php)) {
            return null;
        }

        return $php['version'];
    }

    private function getEnvs(string $section) : array
    {
        $envs = [];
        $lines = explode("\n", $section);

        $inEnvs = false;
        foreach ($lines as $line) {
            $line = trim($line);
            if (! $inEnvs) {
                if (strpos($line, 'env:') === 0) {
                    $inEnvs = true;
                }
                continue;
            }

            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            if (strpos($line, '=') === false) {
                return $envs;
            }

            [$name, $value] = explode('=', $line, 2);
            $envs[$name] = $value;
        }

        return $envs;
    }

    private function getSpaces(string $section) : string
    {
        preg_match('/(^\s*)- DEPS=/m', $section, $spaces);

        return $spaces[1] ?? '';
    }

    /**
     * @return string[]
     */
    private function getSections(string $content) : array
    {
        $offset = 0;

        $sections = [];
        while (preg_match(
            '/(^\s*- php: .*?)(?=^\s*(?:- php|allow_failures)|\n\n)/sm',
            $content,
            $matches,
            0,
            $offset
        )) {
            $offset = strpos($content, $matches[0], $offset) + strlen($matches[1]);
            $sections[] = $matches[1];
        }

        return $sections;
    }

    private function getServicesAndAddons(string $section) : string
    {
        preg_match('/^\s*- php: .*?^\s*env:/sm', $section, $match);

        return $match[0] ?? '';
    }

    private function replace(Repository $repository, string $file) : void
    {
        $content = file_get_contents($file);
        $content = $repository->replace($content);

        $filename = basename($file);

        if ($filename === '.travis.yml') {
            // use always CS_CHECK env variable
            $content = str_replace('CHECK_CS', 'CS_CHECK', $content);

            // Remove IRC in notifications
            $content = preg_replace('/\n^\s*irc:.*$/m', '', $content);

            // Remove sudo: ...
            $content = preg_replace('/^\s*sudo:.*$\n*/m', '', $content);

            // Add fast_finish: true
            $content = preg_replace('/\n^\s*fast_finish:.*$/m', '', $content);
            $content = str_replace('matrix:' . "\n", 'matrix:' . "\n" . '  fast_finish: true' . "\n", $content);

            // Add php linter to script section:
            // @phpcs:disable Generic.Files.LineLength.TooLong
            $lint = "  - find . -path ./vendor -prune -o -type f -name '*.php' -print0 | xargs -0 -n1 -P4 php -l -n | (! grep -v \"No syntax errors detected\")";
            // @phpcs:enable
            $content = preg_replace('/^\s*script:\n/m', '$0' . $lint . "\n", $content);

            $replacements = [];
            $sections = $this->getSections($content);

            if ($sections) {
                foreach ($sections as $k => $section) {
                    $deps = $this->getDeps($section);
                    if ($deps !== 'locked') {
                        continue;
                    }

                    $php = $this->getPhpVersion($section);

                    if ($deps === null || $php === null || ! isset($sections[$k + 1])) {
                        continue;
                    }

                    $nextDeps = $this->getDeps($sections[$k + 1]);
                    $nextPhp = $this->getPhpVersion($sections[$k + 1]);

                    if ($nextDeps !== 'latest' || $nextPhp !== $php) {
                        $replacements[$section] = str_replace('DEPS=locked', 'DEPS=latest', $section);
                        continue;
                    }

                    $oldEnvs = $this->getEnvs($section);
                    $newEnvs = $this->getEnvs($sections[$k + 1]);
                    $spaces = $this->getSpaces($sections[$k + 1]);

                    $additionalEnvs = [];
                    foreach ($oldEnvs as $name => $value) {
                        if (strpos($name, 'LEGACY_DEPS') !== false) {
                            continue;
                        }

                        if (strpos($name, 'CS_CHECK') !== false) {
                            continue;
                        }

                        if (! isset($newEnvs[$name])) {
                            $additionalEnvs[] = $spaces . $name . '=' . $value;
                        }
                    }

                    $addons = $this->getServicesAndAddons($section);
                    $newSection = preg_replace('/^\s*- php.*?env:/sm', $addons, $sections[$k + 1]);
                    if ($additionalEnvs) {
                        $newSection = str_replace(
                            '- DEPS=latest',
                            '- DEPS=latest' . "\n" . implode("\n", $additionalEnvs),
                            $newSection
                        );
                    }
                    $replacements[$sections[$k + 1]] = $newSection;
                    $replacements[$section] = '';
                }

                $content = strtr($content, $replacements);
            }
        }

        if (in_array($filename, ['.gitattributes', '.gitignore'], true)) {
            $rows = explode("\n", $content);

            if ($filename === '.gitattributes') {
                $rows[] = '.ci/ export-ignore';
                $rows[] = '.coveralls.yml export-ignore';
                $rows[] = '.docheader export-ignore';
                $rows[] = '.gitattributes export-ignore';
                $rows[] = '.gitignore export-ignore';
                $rows[] = '.travis.yml export-ignore';
                $rows[] = 'benchmark/ export-ignore';
                $rows[] = 'benchmarks/ export-ignore';
                $rows[] = 'composer.lock export-ignore';
                $rows[] = 'doc/ export-ignore';
                $rows[] = 'docs/ export-ignore';
                $rows[] = 'mkdocs.yml export-ignore';
                $rows[] = 'phpcs.xml export-ignore';
                $rows[] = 'phpstan.neon export-ignore';
                $rows[] = 'phpunit.xml export-ignore';
                $rows[] = 'phpunit.xml.dist export-ignore';
                $rows[] = 'phpunit.xml.travis export-ignore';
                $rows[] = 'test/ export-ignore';

                foreach ($rows as $i => $row) {
                    if (! $row) {
                        continue;
                    }

                    $name = strstr($row, ' ', true);
                    if ($name === false) {
                        $name = $row;
                    }

                    $name = trim($name, '/');
                    if (! file_exists($repository->getPath() . '/' . $name)) {
                        unset($rows[$i]);
                        continue;
                    }

                    $isDir = is_dir($repository->getPath() . '/' . $name);
                    $rows[$i] = '/' . $name . ($isDir ? '/' : '') . ' export-ignore';
                }
            }

            if ($filename === '.gitignore') {
                $hasDocs = (bool) $repository->files('mkdocs.yml');

                if ($hasDocs) {
                    $rows[] = '/laminas-mkdoc-theme.tgz';
                    $rows[] = '/laminas-mkdoc-theme/';
                }

                $rows[] = '/composer.lock';

                foreach ($rows as $i => $row) {
                    $line = trim($row);

                    switch ($line) {
                        case '.DS_STORE':
                        case '.DS_Store':
                        case '.*.sw*':
                        case '.*.un~':
                        case 'php-cs-fixer.phar':
                        case '.buildpath':
                        case '.buildpath/':
                        case '/.idea':
                        case '.idea':
                        case '.idea/':
                        case '/.project':
                        case '.project':
                        case '.project/':
                        case '.settings':
                        case '.settings/':
                        case 'nbproject':
                        case 'nbproject/':
                        case 'tmp/':
                        case 'composer.phar':
                            unset($rows[$i]);
                            break;
                        case 'vendor':
                        case 'doc/html':
                        case 'docs/html':
                        case 'laminas-mkdoc-theme':
                            $rows[$i] = '/' . $line . '/';
                            break;
                        case 'vendor/':
                        case 'doc/html/':
                        case 'docs/html/':
                        case 'phpunit.xml':
                        case 'composer.lock':
                        case 'clover.xml':
                        case 'coveralls-upload.json':
                        case 'laminas-mkdoc-theme.tgz':
                        case 'laminas-mkdoc-theme/':
                            $rows[$i] = '/' . $line;
                            break;
                    }
                }
            }

            $rows = array_unique($rows);

            usort($rows, 'strcasecmp');
            $content = trim(implode("\n", $rows)) . "\n";
        }

        file_put_contents($file, $content);
    }
}
