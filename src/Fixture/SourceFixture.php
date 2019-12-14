<?php

declare(strict_types=1);

namespace Laminas\Transfer\Fixture;

use Laminas\Transfer\Repository;

use function array_merge;
use function array_unique;
use function chdir;
use function dirname;
use function file_get_contents;
use function file_put_contents;
use function getcwd;
use function implode;
use function is_dir;
use function mkdir;
use function preg_match_all;
use function str_ireplace;
use function strpos;
use function strtr;
use function substr;
use function system;
use function trim;

use const PHP_EOL;

/**
 * Change namespace and all references to ZF in source/test files
 * Sort alphabetically imports in PHP files as the namespace has been changed
 */
class SourceFixture extends AbstractFixture
{
    private const SNIFFS = [
        'PSR2.Namespaces.UseDeclaration',
        'WebimpressCodingStandard.Namespaces.AlphabeticallySortedUses',
    ];

    public function process(Repository $repository) : void
    {
        $phps = array_unique(array_merge(
            $repository->files('*.ph*'),
            $repository->files('bin/*'),
            $repository->files('*.twig')
        ));

        foreach ($phps as $k => $php) {
            $this->replace($repository, $php);

            $newName = strtr($php, [
                getcwd() => getcwd(),
                'Zend' => 'Laminas',
                'zend-expressive' => 'mezzio',
                'expressive' => 'mezzio',
                'Expressive' => 'Mezzio',
                'zf-apigility' => 'api-tools',
                'zf-composer-' => 'laminas-composer-',
                'zf-development-' => 'laminas-development-',
                'zf-' => 'api-tools-',
                'zfdeploy.php' => 'laminas-deploy',
                'zendview' => 'laminasview',
                'zend-' => 'laminas-',
                'zfconfig' => 'api-tools-config',
            ]);

            if ($newName !== $php) {
                $dirname = dirname($newName);
                if (! is_dir($dirname)) {
                    mkdir($dirname, 0777, true);
                }
                $repository->move($php, $newName);
                $phps[$k] = $newName;
            }
        }

        $currentDir = getcwd();
        chdir(__DIR__ . '/../../');

        system(
            'vendor/bin/phpcbf --sniffs=' . implode(',', self::SNIFFS) . ' '
            . implode(' ', $phps)
        );

        chdir($currentDir);

        $repository->addReplacedContentFiles($phps);
    }

    private function replace(Repository $repository, string $file) : void
    {
        $content = file_get_contents($file);
        if (! $repository->hasReplacedContent($file)) {
            $content = $repository->replace($content);
        }
        if (strpos($file, '/src/') !== false) {
            $content = $this->deprecatedMethods($content);
        }
        file_put_contents($file, $content);
    }

    /**
     * Because of used splat operator it works only for libraries
     * which supports PHP 5.6+.
     */
    private function deprecatedMethods(string $content) : string
    {
        if (substr(trim($content), -1) !== '}') {
            return $content;
        }

        if (! preg_match_all(
            '/^\s*public\s+(?<static>static\s+)?function\s+(?<name>[^(]*Laminas[^(]*)(?<params>\([^)]*\))/miU',
            $content,
            $matches
        )) {
            return $content;
        }

        $deprecated = '';
        foreach ($matches['name'] as $i => $name) {
            $name = trim($name);
            $legacy = str_ireplace('Laminas', 'Zend', $name);

            // @phpcs:disable Generic.Files.LineLength.TooLong
            $deprecated .= PHP_EOL . '    /**' . PHP_EOL
                . '     * @deprecated Use self::' . $matches['name'][$i] . ' instead' . PHP_EOL
                . '     */' . PHP_EOL
                . '    public ' . $matches['static'][$i] . 'function ' . $legacy . $matches['params'][$i] . PHP_EOL
                . '    {' . PHP_EOL
                . '        return ' . ($matches['static'][$i] ? 'self::' : '$this->') . $name . '(...func_get_args());' . PHP_EOL
                . '    }' . PHP_EOL;
            // @phpcs:enable
        }

        return substr(trim($content), 0, -1)
            . $deprecated
            . '}' . PHP_EOL;
    }
}
