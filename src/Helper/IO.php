<?php

declare(strict_types=1);

namespace Laminas\Transfer\Helper;

use function closedir;
use function copy;
use function is_dir;
use function mkdir;
use function opendir;
use function readdir;
use function sprintf;

class IO
{
    public static function copy(string $source, string $destination) : void
    {
        $dir = opendir($source);
        if (! is_dir($destination)) {
            mkdir($destination);
        }

        while ($file = readdir($dir)) {
            if ($file !== '.' && $file !== '..') {
                if (is_dir($source . '/' . $file)) {
                    self::copy($source . '/' . $file, $destination . '/' . $file);
                } else {
                    copy($source . '/' . $file, $destination . '/' . $file);
                }
            }
        }
        closedir($dir);
    }

    public static function traverseDirectory(string $source) : iterable
    {
        $dir = opendir($source);

        while ($file = readdir($dir)) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $path = sprintf('%s/%s', $source, $file);

            if (! is_dir($path)) {
                yield $path;
                continue;
            }

            yield from self::traverseDirectory($path);
        }

        closedir($dir);
    }
}
