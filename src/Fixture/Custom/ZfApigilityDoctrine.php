<?php

declare(strict_types=1);

namespace Laminas\Transfer\Fixture\Custom;

use Laminas\Transfer\Repository;

/**
 * Process tests assets
 */
class ZfApigilityDoctrine extends ZfApigility
{
    public function process(Repository $repository) : void
    {
        $this->processFiles(
            $repository,
            $repository->files('*/ZFTestApigility*')
        );
    }
}
