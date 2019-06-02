<?php

declare(strict_types=1);

namespace Laminas\Transfer\Fixture\Custom;

use Laminas\Transfer\Fixture\AbstractFixture;
use Laminas\Transfer\Repository;

use function file_get_contents;
use function file_put_contents;
use function str_replace;

class ZendServiceAmazon extends AbstractFixture
{
    public function process(Repository $repository) : void
    {
        $files = $repository->files('*/TestConfiguration.php.dist');

        foreach ($files as $file) {
            $content = file_get_contents($file);
            $content = str_replace('Zend' . PHP_EOL . ' * Framework', 'Laminas' . PHP_EOL . ' * Project', $content);
            file_put_contents($file, $content);
        }

        $files = $repository->files('*.xml');
        foreach ($files as $file) {
            $content = file_get_contents($file);
            $content = str_replace('Zend_', 'Laminas_', $content);
            file_put_contents($file, $content);
        }
    }
}
