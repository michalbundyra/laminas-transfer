<?php

declare(strict_types=1);

namespace Laminas\Transfer\Fixture;

use Laminas\Transfer\Repository;

use function basename;
use function current;
use function explode;
use function file_get_contents;
use function file_put_contents;
use function implode;
use function in_array;
use function sort;
use function trim;

class QAConfigFixture extends AbstractFixture
{
    /** @var string[] */
    private $files = [
        '.gitattributes',
        '.gitignore',
        '.travis.yml',
        'phpcs.xml',
        'phpstan.neon',
        'phpunit.xml',
        'phpunit.xml.dist',
        'phpunit.xml.travis',
    ];

    public function process(Repository $repository) : void
    {
        foreach ($this->files as $fileName) {
            $file = current($repository->files($fileName));
            if ($file) {
                $this->replace($repository, $file);
            }
        }
    }

    protected function replace(Repository $repository, string $file) : void
    {
        $content = file_get_contents($file);
        $content = $repository->replace($content);

        $filename = basename($file);
        if (in_array($filename, ['.gitattributes', '.gitignore'], true)) {
            $rows = explode("\n", $content);
            sort($rows);
            $content = trim(implode("\n", $rows)) . "\n";
        }

        file_put_contents($file, $content);
    }
}
