<?php

declare(strict_types=1);

namespace Laminas\Transfer\Fixture\Custom;

use Laminas\Transfer\Fixture\AbstractFixture;
use Laminas\Transfer\Repository;

use function file_get_contents;
use function file_put_contents;
use function getcwd;
use function str_replace;
use function strtr;

/**
 * Process and rename *.txt tests assets
 */
class ZendMail extends AbstractFixture
{
    public function process(Repository $repository) : void
    {
        $files = $repository->files('*.txt');

        foreach ($files as $file) {
            $content = file_get_contents($file);
            $content = $repository->replace($content);
            file_put_contents($file, $content);

            $newName = strtr($file, [
                getcwd() => getcwd(),
                'zend-' => 'laminas-',
            ]);

            if ($newName !== $file) {
                $repository->move($file, $newName);
            }
        }

        $repository->addReplacedContentFiles($files);

        $files = $repository->files('*/MessageTest.php');
        foreach ($files as $file) {
            $content = file_get_contents($file);
            $content = $repository->replace($content);
            $content = str_replace(
                'O:25:\\"Laminas\\',
                'O:28:\\"Laminas\\',
                $content
            );
            file_put_contents($file, $content);
        }

        $repository->addReplacedContentFiles($files);
    }
}
