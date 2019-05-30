<?php

declare(strict_types=1);

namespace Laminas\Transfer\Fixture;

use Laminas\Transfer\Repository;

use function basename;
use function current;
use function explode;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function implode;
use function in_array;
use function is_dir;
use function sort;
use function strstr;
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

    private function replace(Repository $repository, string $file) : void
    {
        $content = file_get_contents($file);
        $content = $repository->replace($content);

        $filename = basename($file);
        if (in_array($filename, ['.gitattributes', '.gitignore'], true)) {
            $rows = explode("\n", $content);

            if ($filename === '.gitattributes') {
                foreach ($rows as $i => $row) {
                    if (! $row) {
                        continue;
                    }

                    $name = trim(strstr($row, ' ', true), '/');
                    if (! file_exists($repository->getPath() . '/' . $name)) {
                        unset($rows[$i]);
                        continue;
                    }

                    $isDir = is_dir($repository->getPath() . '/' . $name);
                    $rows[$i] = '/' . $name . ($isDir ? '/' : '') . ' export-ignore';
                }
            }

            if ($filename === '.gitignore') {
                foreach ($rows as $i => $row) {
                    $line = trim($row);

                    switch ($line) {
                        case '.DS_STORE':
                        case '.DS_Store':
                        case '.*.sw*':
                        case '.*.un~':
                        case 'php-cs-fixer.phar':
                        case '.buildpath':
                        case '.buildpath/':
                        case '/.idea':
                        case '.idea':
                        case '.idea/':
                        case '/.project':
                        case '.project':
                        case '.project/':
                        case '.settings':
                        case '.settings/':
                        case 'nbproject':
                        case 'nbproject/':
                        case 'tmp/':
                        case 'composer.phar':
                            unset($rows[$i]);
                            break;
                        case 'vendor':
                        case 'doc/html':
                        case 'docs/html':
                            $rows[$i] = '/' . $line . '/';
                            break;
                        case 'vendor/':
                        case 'doc/html/':
                        case 'docs/html/':
                        case 'phpunit.xml':
                        case 'composer.lock':
                        case 'clover.xml':
                        case 'coveralls-upload.json':
                            $rows[$i] = '/' . $line;
                            break;
                    }
                }
            }

            sort($rows);
            $content = trim(implode("\n", $rows)) . "\n";
        }

        file_put_contents($file, $content);
    }
}
