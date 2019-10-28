<?php

declare(strict_types=1);

namespace Laminas\Transfer;

use function array_filter;
use function array_values;
use function strpos;

class ThirdPartyRepository extends Repository
{
    /** @var bool */
    private $installDependencyPlugin;

    /**
     * The name is not relevant to third-party code, and is nullable. However,
     * the path is required.
     */
    public function __construct(string $path, ?string $name, bool $installDependencyPlugin = true)
    {
        parent::__construct($name ?: '', $path);
        $this->installDependencyPlugin = $installDependencyPlugin;
    }

    /**
     * Ignore files under the vendor/ directory
     */
    public function files(string $pattern = '*') : array
    {
        $fileList = parent::files($pattern);

        return array_values(array_filter($fileList, static function (string $file) : bool {
            return strpos($file, '/vendor/') === false;
        }));
    }

    public function installDependencyPlugin() : bool
    {
        return $this->installDependencyPlugin;
    }
}
