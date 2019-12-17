<?php

declare(strict_types=1);

namespace Laminas\Transfer\Fixture\Custom;

use Laminas\Transfer\Fixture\AbstractFixture;
use Laminas\Transfer\Repository;

use function file_get_contents;
use function file_put_contents;
use function str_replace;

/**
 * Update regular expression to skip normalized v2 plugin names.
 */
class ZfApigilityAdmin extends AbstractFixture
{
    public function process(Repository $repository) : void
    {
        $files = $repository->files('*/AbstractPluginManagerModel.php');

        foreach ($files as $file) {
            $content = file_get_contents($file);
            $content = $repository->replace($content);
            $content = str_replace(
                "preg_match('/^laminas(",
                "preg_match('/^(laminas|zend)(",
                $content
            );
            file_put_contents($file, $content);
        }

        $repository->addReplacedContentFiles($files);
    }
}
