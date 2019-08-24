<?php

declare(strict_types=1);

namespace Laminas\Transfer\Fixture;

use Laminas\Transfer\Helper\NamespaceResolver;
use Laminas\Transfer\Repository;

use function file_get_contents;
use function file_put_contents;
use function ltrim;
use function preg_match_all;
use function str_replace;
use function strtr;

use const PHP_EOL;

class MiddlewareAttributesFixture extends AbstractFixture
{
    public function process(Repository $repository) : void
    {
        $files = $repository->files('*/*Middleware.php');
        foreach ($files as $file) {
            $this->processMiddleware($repository, $file);
        }
        $repository->addReplacedContentFiles($files);

        $files = $repository->files('*/*MiddlewareTest.php');
        foreach ($files as $file) {
            $this->processMiddlewareTest($repository, $file);
        }
        $repository->addReplacedContentFiles($files);
    }

    private function processMiddleware(Repository $repository, string $file) : void
    {
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

    private function processMiddlewareTest(Repository $repository, string $file) : void
    {
        $content = file_get_contents($file);

        $namespace = NamespaceResolver::getNamespace($content);
        $uses = NamespaceResolver::getUses($content);

        $content = $repository->replace($content);

        if (preg_match_all(
            '/(?<before>\s*\$[a-z>\s-]*->withAttribute\(\s*)(?<name>[^$)\'"]+?)(?<after>\s*,[^;]*?;)/m',
            $content,
            $matches
        )) {
            $pairs = [];
            foreach ($matches['name'] as $i => $class) {
                $legacyName = NamespaceResolver::getLegacyName($class, $namespace, $uses);
                if ($legacyName === $repository->replace($legacyName)) {
                    continue;
                }

                $replace = $matches[0][$i] . PHP_EOL
                    . ltrim($matches['before'][$i], "\n") . '\\' . $legacyName . $matches['after'][$i];

                $pairs[$matches[0][$i]] = $replace;
            }

            $content = strtr($content, $pairs);
        }

        file_put_contents($file, $content);
    }
}
