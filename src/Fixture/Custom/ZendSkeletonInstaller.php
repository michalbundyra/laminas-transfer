<?php

declare(strict_types=1);

namespace Laminas\Transfer\Fixture\Custom;

use Laminas\Transfer\Fixture\AbstractFixture;
use Laminas\Transfer\Repository;

use function file_get_contents;
use function file_put_contents;
use function preg_replace;
use function str_replace;

/**
 * Support for "zend-skeleton-installer" and "laminas-skeleton-installer" in "extra" section of composer.json.
 */
class ZendSkeletonInstaller extends AbstractFixture
{
    public function process(Repository $repository) : void
    {
        $files = $repository->files('*/OptionalPackagesInstaller.php');

        foreach ($files as $file) {
            $content = file_get_contents($file);
            $content = $repository->replace($content);

            // @phpcs:disable Generic.Files.LineLength.TooLong
            $content = preg_replace(
                '/return isset\(\$extra\[\'laminas-skeleton-installer\'\]\) && is_array\(\$extra\[\'laminas-skeleton-installer\'\]\)\s*\?\s*\$extra\[\'laminas-skeleton-installer\'\]/m',
                'if (isset($extra[\'laminas-skeleton-installer\']) && is_array($extra[\'laminas-skeleton-installer\'])) {'
                . "\n" . '            return $extra[\'laminas-skeleton-installer\'];'
                . "\n" . '        }'
                . "\n\n" . '        // supports legacy "extra.zend-skeleton-installer" configuration'
                . "\n" . '        return isset($extra[\'zend-skeleton-installer\']) && is_array($extra[\'zend-skeleton-installer\'])'
                . "\n" . '            ? $extra[\'zend-skeleton-installer\']',
                $content
            );

            $content = str_replace(
                'unset($json[\'extra\'][\'laminas-skeleton-installer\'])',
                'unset($json[\'extra\'][\'laminas-skeleton-installer\'], $json[\'extra\'][\'zend-skeleton-installer\'])',
                $content
            );
            // @phpcs:enable

            file_put_contents($file, $content);
        }

        $repository->addReplacedContentFiles($files);
    }
}
