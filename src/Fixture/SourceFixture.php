<?php

declare(strict_types=1);

namespace Laminas\Transfer\Fixture;

use Laminas\Transfer\Repository;

use function array_merge;
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
use function str_replace;
use function strpos;
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
        $phps = array_merge(
            $repository->files('*.php'),
            $repository->files('bin/*')
        );
        foreach ($phps as $k => $php) {
            $this->replace($repository, $php);

            if (strpos($php, 'Zend') !== false) {
                $newName = str_replace('Zend', 'Laminas', $php);
                $dirname = dirname($newName);
                if (! is_dir($dirname)) {
                    mkdir($dirname, 0777, true);
                }
                system('git mv ' . $php . ' ' . $newName);
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
    }

    private function replace(Repository $repository, string $file) : void
    {
        $content = file_get_contents($file);
        $content = $repository->replace($content);
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

            // @phpcs:disable
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
