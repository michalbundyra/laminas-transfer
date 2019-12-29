<?php

declare(strict_types=1);

namespace Laminas\Transfer\Fixture\Custom;

use Laminas\Transfer\Fixture\AbstractFixture;
use Laminas\Transfer\Repository;

use function file_get_contents;
use function file_put_contents;

class ZendDi extends AbstractFixture
{
    public function process(Repository $repository) : void
    {
        $files = $repository->files('*.template');
        foreach ($files as $template) {
            $content = file_get_contents($template);
            $content = $repository->replace($content);
            file_put_contents($template, $content);
        }
        $repository->addReplacedContentFiles($files);
    }
}
