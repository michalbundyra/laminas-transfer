<?php

declare(strict_types=1);

namespace Laminas\Transfer\Fixture\Custom;

use Laminas\Transfer\Fixture\AbstractFixture;
use Laminas\Transfer\Repository;

use function file_get_contents;
use function file_put_contents;

/**
 * Process *.xml files
 */
class ZendPaginator extends AbstractFixture
{
    public function process(Repository $repository) : void
    {
        $files = $repository->files('*.xml');

        foreach ($files as $file) {
            $content = file_get_contents($file);
            $content = $repository->replace($content);
            file_put_contents($file, $content);
        }

        $repository->addReplacedContentFiles($files);
    }
}
