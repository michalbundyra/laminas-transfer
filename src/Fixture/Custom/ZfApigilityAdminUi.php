<?php

declare(strict_types=1);

namespace Laminas\Transfer\Fixture\Custom;

use Laminas\Transfer\Repository;

use function array_merge;
use function array_unique;

/**
 * Rename /apigility-ui/ directory and process all files inside.
 * Process all *.js and *.html files.
 * Process component manager files: bower.json and package.json.
 */
class ZfApigilityAdminUi extends ZfApigility
{
    public function process(Repository $repository) : void
    {
        $this->processFiles(
            $repository,
            array_unique(array_merge(
                $repository->files('*/apigility-ui/*'),
                $repository->files('*.html'),
                $repository->files('*.js')
            ))
        );

        $this->processJsons($repository);
    }
}
