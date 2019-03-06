<?php

declare(strict_types=1);

namespace Laminas\Transfer\Fixture;

use Laminas\Transfer\Repository;

use function array_merge;
use function basename;
use function current;
use function date;
use function dirname;
use function file_get_contents;
use function file_put_contents;
use function preg_replace;
use function strtr;
use function system;

/**
 * Updates documentation files in doc/ or docs/ directories (*.html, *.md)
 * Renames files with "zend-"/"zf-" names
 * Updates README.md if present
 * Updates mkdocs.yml if present
 */
class DocsFixture extends AbstractFixture
{
    public function process(Repository $repository) : void
    {
        $docs = array_merge(
            $repository->files('doc/*.md'),
            $repository->files('doc/*.html'),
            $repository->files('docs/*.md'),
            $repository->files('docs/*.html')
        );

        foreach ($docs as $doc) {
            $this->replace($repository, $doc);

            $dirname = dirname($doc);
            $filename = basename($doc);
            $newName = strtr($filename, ['zend-' => 'laminas-', 'zf-' => 'laminas-']);

            if ($newName !== $filename) {
                system('cd ' . $dirname . ' && git mv ' . $filename . ' ' . $newName);
            }
        }

        $readme = current($repository->files('README.md'));
        if ($readme) {
            $this->replace($repository, $readme);
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
