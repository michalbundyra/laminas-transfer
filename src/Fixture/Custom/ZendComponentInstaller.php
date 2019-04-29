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
 * Support for "zf" and "laminas" in "extra" section of composer.json.
 */
class ZendComponentInstaller extends AbstractFixture
{
    public function process(Repository $repository) : void
    {
        $files = $repository->files('*/ComponentInstaller.php');

        foreach ($files as $file) {
            $content = file_get_contents($file);
            $content = str_replace('the zf-specific metadata', 'the metadata', $content);
            $content = $repository->replace($content);
            $content = preg_replace(
                '/return isset\(\$extra\[\'laminas\'\]\) && is_array\(\$extra\[\'laminas\'\]\)\s*\?\s*\$extra\[\'laminas\'\]/m',
                'if (isset($extra[\'laminas\']) && is_array($extra[\'laminas\'])) {'
                    . "\n" . '            return $extra[\'laminas\'];'
                    . "\n" . '        }'
                    . "\n\n" . '        // supports legacy "extra.zf" configuration'
                    . "\n" . '        return isset($extra[\'zf\']) && is_array($extra[\'zf\'])'
                    . "\n" . '            ? $extra[\'zf\']',
                $content
            );

            file_put_contents($file, $content);
        }

        $repository->addReplacedContentFiles($files);
    }
}
