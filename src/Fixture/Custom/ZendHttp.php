<?php

declare(strict_types=1);

namespace Laminas\Transfer\Fixture\Custom;

use Laminas\Transfer\Fixture\AbstractFixture;
use Laminas\Transfer\Repository;

use function array_merge;
use function basename;
use function dirname;
use function file_get_contents;
use function file_put_contents;
use function preg_replace;

/**
 * Process *.txt/*.php files (test assets)
 * Rename ZF\d+ files to Laminas\d+
 */
class ZendHttp extends AbstractFixture
{
    public function process(Repository $repository) : void
    {
        $files = array_merge(
            $repository->files('*.php'),
            $repository->files('*.txt')
        );

        foreach ($files as $file) {
            $content = file_get_contents($file);
            $content = $repository->replace($content);

            file_put_contents($file, $content);

            $filename = basename($file);
            $dirname = dirname($file);
            $newName = preg_replace('/^ZF(\d+)/', 'Laminas$1', $filename);

            if ($newName !== $filename) {
                $repository->move($file, $dirname . '/' . $newName);
            }
        }

        $repository->addReplacedContentFiles($files);
    }
}
