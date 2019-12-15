<?php

declare(strict_types=1);

namespace Laminas\Transfer\Fixture\Custom;

use Laminas\Transfer\Fixture\AbstractFixture;
use Laminas\Transfer\Repository;

use function array_merge;
use function basename;
use function dirname;
use function file_get_contents;
use function file_put_contents;
use function preg_replace;
use function strtr;

/**
 * Process *.txt/*.php/*.xml files (test assets)
 * Rename ZF\d+ files to Laminas\d+
 */
class ZendXmlrpc extends AbstractFixture
{
    public function process(Repository $repository) : void
    {
        $files = array_merge(
            $repository->files('*.php'),
            $repository->files('*.txt'),
            $repository->files('*.xml')
        );

        foreach ($files as $file) {
            $content = file_get_contents($file);
            $content = $repository->replace($content);

            $filename = basename($file);
            if ($filename === 'HttpTest.php') {
                // Because we change Zend to Laminas (+3 chars)
                $content = strtr($content, [
                    "'Content-Length' => 958" => "'Content-Length' => 961",
                    'Content-Length: 958' => 'Content-Length: 961',
                ]);
            }

            file_put_contents($file, $content);

            $dirname = dirname($file);
            $newName = preg_replace('/^ZF(\d+)/', 'Laminas$1', $filename);

            if ($newName !== $filename) {
                $repository->move($file, $dirname . '/' . $newName);
            }
        }

        $repository->addReplacedContentFiles($files);
    }
}
