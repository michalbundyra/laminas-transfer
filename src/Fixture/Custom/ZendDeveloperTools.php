<?php

declare(strict_types=1);

namespace Laminas\Transfer\Fixture\Custom;

use Laminas\Transfer\Fixture\AbstractFixture;
use Laminas\Transfer\Repository;

use function array_merge;
use function basename;
use function current;
use function dirname;
use function file_get_contents;
use function file_put_contents;
use function preg_replace;
use function strtr;

/**
 * Renames view/zend-developer-tools to view/laminas-developer-tools
 * Rewrites JS, CSS, PH* files with additional rules
 * Renames zenddevelopertools file to laminas-developer-tools
 * Renames zendframework file to laminas
 * Removes invalid configuration option from phpunit.xml.dist
 */
class ZendDeveloperTools extends AbstractFixture
{
    public function process(Repository $repository) : void
    {
        $legacyPath = $repository->getPath() . '/view/zend-developer-tools';
        $newPath = $repository->getPath() . '/view/' . $repository->replace('zend-developer-tools');
        $repository->move($legacyPath, $newPath);

        $files = array_merge(
            $repository->files('*zenddevelopertools*'),
            $repository->files('*zendframework*')
        );
        foreach ($files as $file) {
            $basename = basename($file);
            $newFile = dirname($file) . '/' . $repository->replace($basename);
            $repository->move($file, $newFile);
        }

        $phpunitConfig = current($repository->files('phpunit.xml.dist'));
        if ($phpunitConfig) {
            $content = file_get_contents($phpunitConfig);
            $content = preg_replace('/^\s+syntaxCheck="true"$\n?/m', '', $content);
            file_put_contents($phpunitConfig, $content);
        }

        $files = array_merge(
            $repository->files('*.js'),
            $repository->files('*.css'),
            $repository->files('*.ph*')
        );

        foreach ($files as $file) {
            $content = file_get_contents($file);
            if (! $repository->hasReplacedContent($file)) {
                $content = $repository->replace($content);
            }
            // @phpcs:disable Generic.Files.LineLength.TooLong
            $content = strtr($content, [
                'zdf-' => 'laminas-',
                'zdt-' => 'laminas-',
                'ZDT_Laminas_' => 'Laminas_Developer_Tool_',
                'http://modules.laminas.dev/' => 'https://packagist.org/?tags=module~zf2~zendframework~zend%20framework~zend%20framework%202~zf3~zf~zend~laminas',
            ]);
            // @phpcs:enable

            file_put_contents($file, $content);
        }

        $repository->addReplacedContentFiles($files);
    }
}
