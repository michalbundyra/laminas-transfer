<?php

declare(strict_types=1);

namespace Laminas\Transfer\Fixture;

use Laminas\Transfer\Repository;

use function class_exists;
use function explode;
use function str_replace;
use function strtr;
use function ucwords;

class CustomFixture extends AbstractFixture
{
    public function process(Repository $repository) : void
    {
        [$org, $name] = explode('/', $repository->getName());

        $class = __NAMESPACE__ . '\\Custom\\'
            . str_replace(' ', '', ucwords(strtr($name, ['-' => ' ', '_' => ' '])));

        if (class_exists($class)) {
            /** @var AbstractFixture $obj */
            $obj = new $class($this->output);
            $obj->process($repository);
        }
    }
}
