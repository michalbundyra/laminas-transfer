<?php

declare(strict_types=1);

namespace Laminas\Transfer\Fixture;

use Laminas\Transfer\Repository;

use function array_unique;
use function basename;
use function current;
use function explode;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function implode;
use function in_array;
use function is_dir;
use function preg_replace;
use function str_replace;
use function strpos;
use function strstr;
use function trim;
use function usort;

class QAConfigFixture extends AbstractFixture
{
    /** @var string[] */
    private $files = [
        '.gitattributes',
        '.gitignore',
        '.travis.yml',
        'phpcs.xml',
        'phpstan.neon',
        'phpunit.xml',
        'phpunit.xml.dist',
        'phpunit.xml.travis',
        'Makefile',
        'Vagrantfile',
    ];

    public function process(Repository $repository) : void
    {
        foreach ($this->files as $fileName) {
            $file = current($repository->files($fileName));
            if ($file) {
                $this->replace($repository, $file);
            }
        }
    }

    private function replace(Repository $repository, string $file) : void
    {
        $content = file_get_contents($file);
        $content = $repository->replace($content, strpos($file, 'mkdocs.yml') !== false);

        $filename = basename($file);

        if ($filename === '.travis.yml') {
            // Remove IRC in notifications
            $content = preg_replace('/\n^\s*irc:.*$/m', '', $content);

            // Remove sudo: ...
            $content = preg_replace('/^\s*sudo:.*$\n*/m', '', $content);

            // Add fast_finish: true
            $content = preg_replace('/\n^\s*fast_finish:.*$/m', '', $content);
            $content = str_replace('matrix:' . "\n", 'matrix:' . "\n" . '  fast_finish: true' . "\n", $content);
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
