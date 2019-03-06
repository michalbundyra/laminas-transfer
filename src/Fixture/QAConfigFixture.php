<?php

declare(strict_types=1);

namespace Laminas\Transfer\Fixture;

use Laminas\Transfer\Repository;

use function current;
use function file_get_contents;
use function file_put_contents;

class QAConfigFixture extends AbstractFixture
{
    /** @var string[] */
    private $files = [
        '.gitattributes',
        '.gitignore',
        'phpcs.xml',
        'phpunit.xml',
        'phpunit.xml.dist',
    ];

    public function process(Repository $repository) : void
    {
        foreach ($this->files as $fileName) {
            $file = current($repository->files($fileName));
            if ($file) {
                $this->replace($repository, $file);
            } else {
                $this->writeln('<error>SKIP</error> Missing <info>' . $fileName . '</info> file.');
            }
        }
    }

    protected function replace(Repository $repository, string $file) : void
    {
        $content = file_get_contents($file);
        $content = $repository->replace($content);
        file_put_contents($file, $content);
    }
}
