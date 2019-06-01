<?php

declare(strict_types=1);

namespace Laminas\Transfer\Helper;

use function closedir;
use function copy;
use function is_dir;
use function mkdir;
use function opendir;
use function readdir;

class IO
{
    public static function copy(string $source, string $destination) : void
    {
        $dir = opendir($source);
        mkdir($destination);

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
}
