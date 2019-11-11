<?php

declare(strict_types=1);

namespace Laminas\Transfer\Fixture\Custom;

use Laminas\Transfer\Fixture\AbstractFixture;
use Laminas\Transfer\Repository;

use function file_get_contents;
use function file_put_contents;
use function preg_replace;
use function rename;
use function str_repeat;
use function str_replace;
use function system;

use const PHP_EOL;

/**
 * Replaces zend-framework.flf file
 */
class ZendText extends AbstractFixture
{
    public function process(Repository $repository) : void
    {
        $files = $repository->files('*/zend-framework.flf');

        foreach ($files as $file) {
            $content = file_get_contents($file);
            $content = $repository->replace($content);

            // @phpcs:disable Generic.Files.LineLength.TooLong
            $content = preg_replace(
                '/^-{20}.*-{20}$\n\n/ms',
                '---------------------------------------------------------------------------------------------' . PHP_EOL
                . ' * @see       https://github.com/laminas/laminas-text for the canonical source repository' . PHP_EOL
                . ' * @copyright https://github.com/laminas/laminas-text/blob/master/COPYRIGHT.md' . PHP_EOL
                . ' * @license   https://github.com/laminas/laminas-text/blob/master/LICENSE.md New BSD License' . PHP_EOL
                . '---------------------------------------------------------------------------------------------' . PHP_EOL . PHP_EOL
                . str_repeat('$ #' . PHP_EOL, 12),
                $content
            );
            // @phpcs:enable

            file_put_contents($file, $content);

            $newName = str_replace('zend-framework', 'laminas-project', $file);
            if ($repository->isUnderGit()) {
                system('git mv ' . $file . ' ' . $newName);
            } else {
                rename($file, $newName);
            }
        }

        $repository->addReplacedContentFiles($files);
    }
}
