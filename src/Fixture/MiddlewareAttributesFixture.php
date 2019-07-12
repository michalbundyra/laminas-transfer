<?php

declare(strict_types=1);

namespace Laminas\Transfer\Fixture;

use Laminas\Transfer\Helper\NamespaceResolver;
use Laminas\Transfer\Repository;

use function file_get_contents;
use function file_put_contents;
use function preg_match_all;
use function str_replace;
use function strpos;
use function strstr;

class MiddlewareAttributesFixture extends AbstractFixture
{
    public function process(Repository $repository) : void
    {
        $files = $repository->files('*/*Middleware.php');

        foreach ($files as $file) {
            $content = file_get_contents($file);

            $namespace = NamespaceResolver::getNamespace($content);
            $uses = NamespaceResolver::getUses($content);

            $content = $repository->replace($content);

            if (preg_match_all(
                '/->withAttribute\(([^:$\'")]+::class),\s*([^)]+)\)/',
                $content,
                $matches
            )) {
                foreach ($matches[1] as $i => $class) {
                    $legacyName = $this->getLegacyName($class, $namespace, $uses);
                    $replace = $matches[0][$i] . '->withAttribute(\\' . $legacyName . ', ' . $matches[2][$i] . ')';
                    $content = str_replace($matches[0][$i], $replace, $content);
                }
            }

            file_put_contents($file, $content);
        }

        $repository->addReplacedContentFiles($files);
    }

    private function getLegacyName(string $class, string $namespace, array $uses) : string
    {
        $className = str_replace('::class', '', $class);
        if (strpos($className, '\\') !== false) {
            $className = strstr($className, '\\', true);
        }

        return isset($uses[$className]) ? $uses[$className] . '::class' : $namespace . '\\' . $class;
    }
}
