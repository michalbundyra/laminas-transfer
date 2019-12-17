<?php

declare(strict_types=1);

namespace Laminas\Transfer\Fixture\Custom;

use Laminas\Transfer\Repository;

/**
 * Rename /zf-* directories and process all files inside.
 */
class ZfAssetManager extends ZfApigility
{
    public function process(Repository $repository) : void
    {
        $this->processFiles(
            $repository,
            $repository->files('*/zf-*')
        );
    }
}
