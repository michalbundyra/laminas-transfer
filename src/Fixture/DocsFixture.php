<?php

declare(strict_types=1);

namespace Laminas\Transfer\Fixture;

use Laminas\Transfer\Repository;

use function array_merge;
use function basename;
use function current;
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
 * Renames .zf-mkdoc-theme-landing
 * Updates contents of CONTRIBUTING/CODE_OF_CONDUCT/SUPPORT files
 */
class DocsFixture extends AbstractFixture
{
    private const CONDUCT_FILES = [
        'docs/CODE_OF_CONDUCT.md',
        'doc/CODE_OF_CONDUCT.md',
        'CODE_OF_CONDUCT.md',
        'docs/CONDUCT.md',
        'doc/CONDUCT.md',
        'CONDUCT.md',
    ];

    private const CONTRIBUTING_FILES = [
        'docs/CONTRIBUTING.md',
        'doc/CONTRIBUTING.md',
        'CONTRIBUTING.md',
    ];

    private const SUPPORT_FILES = [
        'docs/SUPPORT.md',
        'doc/SUPPORT.md',
        'SUPPORT.md',
    ];

    private const FILES = [
        Repository::T_CONDUCT => self::CONDUCT_FILES,
        Repository::T_CONTRIBUTING => self::CONTRIBUTING_FILES,
        Repository::T_SUPPORT => self::SUPPORT_FILES,
    ];

    public function process(Repository $repository) : void
    {
        $docs = array_merge(
            $repository->files('doc/*'),
            $repository->files('docs/*')
        );

        foreach ($docs as $doc) {
            $this->replace($repository, $doc);

            $dirname = dirname($doc);
            $filename = basename($doc);
            $newName = $dirname . '/' . strtr($filename, [
                'zend-expressive' => 'expressive',
                'zend-' => 'laminas-',
                'zf-' => 'laminas-',
            ]);

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

            // remove copyright as it is no longer required there
            $content = preg_replace('/^copyright: .*?$\n/m', '', $content);
            file_put_contents($mkdocs, $content);
        }

        $mkdocsTheme = current($repository->files('.zf-mkdoc-theme-landing'));
        if ($mkdocsTheme) {
            $newName = str_replace('zf-', 'laminas-', $mkdocsTheme);
            system('git mv ' . $mkdocsTheme . ' ' . $newName);
        }

        foreach (self::FILES as $template => $files) {
            foreach ($files as $fileName) {
                $file = current($repository->files($fileName));
                if ($file) {
                    file_put_contents($file, $repository->getTemplateText($template));
                }
            }
        }
    }

    private function replace(Repository $repository, string $file) : void
    {
        $content = file_get_contents($file);
        $content = $repository->replace($content);
        file_put_contents($file, $content);
    }
}
