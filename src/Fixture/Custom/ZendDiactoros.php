<?php

declare(strict_types=1);

namespace Laminas\Transfer\Fixture\Custom;

use Laminas\Transfer\Fixture\AbstractFixture;
use Laminas\Transfer\Repository;

use function file_get_contents;
use function file_put_contents;
use function preg_replace;

class ZendDiactoros extends AbstractFixture
{
    public function process(Repository $repository) : void
    {
        $files = $repository->files('*/marshal_uri_from_sapi.php');

        foreach ($files as $file) {
            $content = file_get_contents($file);
            $content = preg_replace(
                '/\* From ZF2\'s Zend\\\\Http\\\\PhpEnvironment\\\\Request class\s+'
                . '\* @copyright.*?$\s+'
                . '\* @license.*?$/m',
                '* From Laminas\\Http\\PhpEnvironment\\Request class',
                $content
            );

            file_put_contents($file, $content);
        }
    }
}
