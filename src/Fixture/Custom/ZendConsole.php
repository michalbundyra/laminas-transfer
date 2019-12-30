<?php

declare(strict_types=1);

namespace Laminas\Transfer\Fixture\Custom;

use Laminas\Transfer\Fixture\AbstractFixture;
use Laminas\Transfer\Repository;

use function file_get_contents;
use function file_put_contents;
use function preg_replace;

class ZendConsole extends AbstractFixture
{
    public function process(Repository $repository) : void
    {
        // Provide legacy constant Zend\Console\Getopt::MODE_ZEND, equivalant to
        // Zend\Console\Getopt::MODE_LAMINAS
        $files = $repository->files('*/Getopt.php');

        foreach ($files as $file) {
            $content = file_get_contents($file);
            $content = $repository->replace($content);
            $content = preg_replace(
                '/^(\s*)const MODE_ZEND(\s*\=\s*)(\'[^\']+\')\s*;\s*$/m',
                '$1/** @deprecated Use MODE_LAMINAS instead */' . "\n"
                . '$1const MODE_ZEND    = $3;' . "\n"
                . '$1const MODE_LAMINAS = $3;' . "\n",
                $content
            );

            file_put_contents($file, $content);
        }

        $repository->addReplacedContentFiles($files);
    }
}
