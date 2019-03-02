<?php

declare(strict_types=1);

namespace Laminas\Transfer\Fixture;

use Laminas\Transfer\Repository;

use function current;
use function date;
use function file_get_contents;
use function file_put_contents;
use function preg_replace;
use function sprintf;
use function var_export;

/**
 * Updates LICENSE.md
 * Updates license headers in all *.php files
 * Adds COPYRIGHT.md file
 * Updates .docheader if exist
 */
class LicenseFixture extends AbstractFixture
{
    private const HEADER = <<<'EOS'
/**
 * @see       https://github.com/%1$s for the canonical source repository
 * @copyright https://github.com/%1$s/blob/master/COPYRIGHT.md
 * @license   https://github.com/%1$s/blob/master/LICENSE.md New BSD License
 */
EOS;

    public function process(Repository $repository) : void
    {
        $license = current($repository->files('LICENSE.md'));

        if ($license) {
            $content = file_get_contents($license);
            $content = preg_replace(
                '/Copyright \(c\) (\d+-)?\d+, Zend Technologies USA, Inc./',
                'Copyright (c) ' . date('Y') . ' Laminas',
                $content
            );
            file_put_contents($license, $content);
        }

        $phps = $repository->files('*.php');
        $this->writeln(var_export($phps, true));
        foreach ($phps as $php) {
            $this->replace($repository, $php);
        }

        file_put_contents(
            $repository->getPath() . '/COPYRIGHT.md',
            'Copyright (c) ' . date('Y') . ', Laminas. All rights reserved. (https://www.laminas.org)'
        );

        $docheader = current($repository->files('.docheader'));
        if ($docheader) {
            $this->replace($repository, $docheader);
        }
    }

    protected function replace(Repository $repository, string $file) : void
    {
        $content = file_get_contents($file);

        $content = preg_replace(
            '/\/\*\*.+?@license.+? \*\//s',
            sprintf(self::HEADER, $repository->getNewName()),
            $content
        );

        file_put_contents($file, $content);
    }
}
