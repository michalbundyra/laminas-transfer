<?php

declare(strict_types=1);

namespace Laminas\Transfer\Fixture;

use Laminas\Transfer\Repository;

use function current;
use function file_get_contents;
use function file_put_contents;

class TravisFixture extends AbstractFixture
{
    public function process(Repository $repository) : void
    {
        $travis = current($repository->files('.travis.yml'));

        if (! $travis) {
            return;
        }

        $content = file_get_contents($travis);
        $content = $repository->replace($content);
        file_put_contents($travis, $content);
    }
}
