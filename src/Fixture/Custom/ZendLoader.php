<?php

declare(strict_types=1);

namespace Laminas\Transfer\Fixture\Custom;

use Laminas\Transfer\Fixture\AbstractFixture;
use Laminas\Transfer\Repository;

use function file_get_contents;
use function file_put_contents;
use function preg_replace;

/**
 * Adds legacy constant name AUTOREGISTER_ZF and mark it as deprecated
 * Removes registering LaminasXml namespace as we are using now Laminas\Xml
 * Removes test expectations for LaminasXml namespace
 */
class ZendLoader extends AbstractFixture
{
    public function process(Repository $repository) : void
    {
        $files = $repository->files('*/StandardAutoloader.php');

        foreach ($files as $file) {
            $content = file_get_contents($file);
            $content = preg_replace(
                '/\s*\$this->registerNamespace\(\s*\'ZendXml\',.*?\);/ms',
                '',
                $content
            );
            $content = $repository->replace($content);
            $content = preg_replace(
                '/^(\s*)const AUTOREGISTER_(.*?)( .*?;)/m',
                '$1/** @deprecated Use AUTOREGISTER_LAMINAS instead */' . "\n"
                . '$1const AUTOREGISTER_ZF$3' . "\n" . '$0',
                $content
            );

            file_put_contents($file, $content);
        }

        $repository->addReplacedContentFiles($files);

        // Replace expectation in test file
        $files = $repository->files('*/StandardAutoloaderTest.php');

        foreach ($files as $file) {
            $content = file_get_contents($file);
            $content = preg_replace(
                '/^\s*\'ZendXml\\\\\\\\\' => .*?,$\n/m',
                '',
                $content
            );
            $content = $repository->replace($content);

            file_put_contents($file, $content);
        }

        $repository->addReplacedContentFiles($files);
    }
}
