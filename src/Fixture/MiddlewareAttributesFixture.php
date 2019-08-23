<?php

declare(strict_types=1);

namespace Laminas\Transfer\Fixture;

use Laminas\Transfer\Helper\NamespaceResolver;
use Laminas\Transfer\Repository;

use function file_get_contents;
use function file_put_contents;
use function preg_match_all;
use function str_replace;

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
                '/(\s*->withAttribute\(\s*)([^:$\'")]+::class)(,\s*)([^)]+)(\s*\))/',
                $content,
                $matches
            )) {
                foreach ($matches[2] as $i => $class) {
                    $legacyName = '\\' . NamespaceResolver::getLegacyName($class, $namespace, $uses);
                    $replace = $matches[0][$i] . $matches[1][$i]
                        . $legacyName . $matches[3][$i] . $matches[4][$i] . $matches[5][$i];
                    $content = str_replace($matches[0][$i], $replace, $content);
                }
            }

            file_put_contents($file, $content);
        }

        $repository->addReplacedContentFiles($files);
    }
}
