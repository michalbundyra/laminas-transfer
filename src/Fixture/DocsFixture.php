<?php

declare(strict_types=1);

namespace Laminas\Transfer\Fixture;

use Laminas\Transfer\Repository;

use function basename;
use function current;
use function date;
use function dirname;
use function file_get_contents;
use function file_put_contents;
use function preg_replace;
use function str_replace;
use function system;

/**
 * Updates documentation files docs/
 * Updates mkdocs.yml
 * Renames files with "zend" names
 */
class DocsFixture extends AbstractFixture
{
    public function process(Repository $repository) : void
    {
        $docs = $repository->files('docs/*.md');
        foreach ($docs as $doc) {
            $this->replace($repository, $doc);

            $dirname = dirname($doc);
            $filename = basename($doc);
            $newName = str_replace('zend-', 'laminas-', $filename);

            if ($newName !== $filename) {
                system('cd ' . $dirname . ' && git mv ' . $filename . ' ' . $newName);
            }
        }

        $mkdocs = current($repository->files('mkdocs.yml'));
        if ($mkdocs) {
            $content = file_get_contents($mkdocs);
            $content = $repository->replace($content);
            $content = preg_replace(
                '/Copyright \(c\) (\d{4}-)?\d{4} /',
                'Copyright (c) ' . date('Y') . ' ',
                $content
            );
            file_put_contents($mkdocs, $content);
        }
    }

    private function replace(Repository $repository, string $file) : void
    {
        $content = file_get_contents($file);
        $content = $repository->replace($content);
        file_put_contents($file, $content);
    }
}
