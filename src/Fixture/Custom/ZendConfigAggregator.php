<?php

declare(strict_types=1);

namespace Laminas\Transfer\Fixture\Custom;

use Laminas\Transfer\Fixture\AbstractFixture;
use Laminas\Transfer\Repository;

use function file_get_contents;
use function file_put_contents;
use function getcwd;
use function strtr;

/**
 * Process and rename /zend-config/* files
 */
class ZendConfigAggregator extends AbstractFixture
{
    public function process(Repository $repository) : void
    {
        $files = $repository->files('*/zend-config/*');
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
    }
}
