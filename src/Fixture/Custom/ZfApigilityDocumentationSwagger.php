<?php

declare(strict_types=1);

namespace Laminas\Transfer\Fixture\Custom;

use Laminas\Transfer\Repository;

/**
 * Process all assets.
 */
class ZfApigilityDocumentationSwagger extends ZfApigility
{
    public function process(Repository $repository) : void
    {
        $this->processFiles(
            $repository,
            $repository->files('asset/*')
        );
    }
}
