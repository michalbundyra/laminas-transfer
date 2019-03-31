<?php

declare(strict_types=1);

namespace Laminas\Transfer\Helper;

use function file_put_contents;
use function json_encode;

use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_SLASHES;
use const PHP_EOL;

class JsonWriter
{
    /**
     * @param array|object $data
     * @return bool|int
     */
    public static function write(string $file, $data)
    {
        $content = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;

        return file_put_contents($file, $content);
    }
}
