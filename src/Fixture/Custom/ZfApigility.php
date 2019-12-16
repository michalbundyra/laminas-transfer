<?php

declare(strict_types=1);

namespace Laminas\Transfer\Fixture\Custom;

use Laminas\Transfer\Fixture\AbstractFixture;
use Laminas\Transfer\Repository;

use function basename;
use function file_get_contents;
use function file_put_contents;
use function preg_replace;
use function str_replace;
use function strtr;

/**
 * Rename /zf-apigility/ directory and process all files inside.
 */
class ZfApigility extends AbstractFixture
{
    public function process(Repository $repository) : void
    {
        $files = $repository->files('asset/*');

        foreach ($files as $file) {
            $content = file_get_contents($file);
            if (basename($file) === 'package.json') {
                $content = strtr($content, [
                    '"email": "apigility-users@zend.com",' => '',
                    '"irc": "irc://irc.freenode.net/apigility",' => '',
                ]);
                $content = preg_replace('/^\s*$\n/m', '', $content);
            }
            $content = $repository->replace($content);

            file_put_contents($file, $content);

            $newName = str_replace('/asset/zf-apigility/', '/asset/api-tools/', $file);
            if ($file !== $newName) {
                $repository->move($file, $newName);
            }
        }

        $repository->addReplacedContentFiles($files);
    }
}
