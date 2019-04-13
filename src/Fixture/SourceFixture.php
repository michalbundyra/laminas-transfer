<?php

declare(strict_types=1);

namespace Laminas\Transfer\Fixture;

use Laminas\Transfer\Repository;

use function array_merge;
use function chdir;
use function file_get_contents;
use function file_put_contents;
use function getcwd;
use function implode;
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
        foreach ($phps as $php) {
            $this->replace($repository, $php);
        }

        $currentDir = getcwd();
        chdir(__DIR__ . '/../../');

        system(
            'vendor/bin/phpcbf --sniffs=' . implode(',', self::SNIFFS) . ' '
                . implode(' ', $phps)
        );

        chdir($currentDir);
    }

    protected function replace(Repository $repository, string $file) : void
    {
        $content = file_get_contents($file);
        $content = $repository->replace($content);
        file_put_contents($file, $content);
    }
}
