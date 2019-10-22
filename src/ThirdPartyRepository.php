<?php

declare(strict_types=1);

namespace Laminas\Transfer;

use function array_values;
use function preg_match;

class ThirdPartyRepository extends Repository
{
    /**
     * The name is not relevant to third-party code, and is nullable. However,
     * the path is required.
     */
    public function __construct(string $path, ?string $name = null)
    {
        parent::__construct($name ?: '', $path);
    }

    /**
     * Ignore files under the vendor/ directory
     */
    public function files(string $pattern = '*') : array
    {
        $fileList = parent::files($pattern);
        foreach ($fileList as $index => $file) {
            if (preg_match('#/vendor/#', $file)) {
                unset($fileList[$index]);
            }
        }
        return array_values($fileList);
    }
}
