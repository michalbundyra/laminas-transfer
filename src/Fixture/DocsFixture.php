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
use function str_replace;
use function strpos;
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
            $newName = $dirname . '/' . strtr($filename, ['zend-' => 'laminas-', 'zf-' => 'laminas-']);

            if (strpos($doc, 'router/zf2.md') !== false) {
                $newName = str_replace('zf2.md', 'laminas-router.md', $doc);
            }

            if ($newName !== $doc) {
                system('git mv ' . $doc . ' ' . $newName);
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
                '/^copyright: .*?$/m',
                'copyright: Copyright (c) ' . date('Y') . ' <a href="https://getlaminas.org">Laminas Foundation</a>',
                $content
            );
            file_put_contents($mkdocs, $content);
        }

        $mkdocsTheme = current($repository->files('.zf-mkdoc-theme-landing'));
        if ($mkdocsTheme) {
            $newName = str_replace('zf-', 'laminas-', $mkdocsTheme);
            system('git mv ' . $mkdocsTheme . ' ' . $newName);
        }
    }

    private function replace(Repository $repository, string $file) : void
    {
        $content = file_get_contents($file);
        $content = $repository->replace($content);
        file_put_contents($file, $content);
    }
}
