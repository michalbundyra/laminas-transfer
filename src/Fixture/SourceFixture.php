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
use function str_replace;
use function strpos;
use function system;

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
        file_put_contents($file, $content);
    }
}
