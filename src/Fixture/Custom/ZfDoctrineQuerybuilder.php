<?php

declare(strict_types=1);

namespace Laminas\Transfer\Fixture\Custom;

use Laminas\Transfer\Repository;

use function array_merge;

/**
 * Process *.yml/*.xml files (configuration in tests)
 */
class ZfDoctrineQuerybuilder extends ZfApigility
{
    public function process(Repository $repository) : void
    {
        $this->processFiles(
            $repository,
            array_merge(
                $repository->files('*.yml'),
                $repository->files('*.xml')
            )
        );
    }
}
