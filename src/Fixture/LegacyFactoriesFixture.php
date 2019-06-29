<?php

declare(strict_types=1);

namespace Laminas\Transfer\Fixture;

use Laminas\Transfer\Helper\NamespaceResolver;
use Laminas\Transfer\Repository;

use function file_get_contents;
use function file_put_contents;
use function lcfirst;
use function microtime;
use function preg_match_all;
use function str_repeat;
use function str_replace;
use function strlen;
use function strpos;
use function strstr;
use function uniqid;

use const PHP_EOL;

class LegacyFactoriesFixture extends AbstractFixture
{
    public function process(Repository $repository) : void
    {
        $files = $repository->files('*/*Factory.php');

        foreach ($files as $file) {
            $content = file_get_contents($file);
            if (! $namespace = NamespaceResolver::getNamespace($content)) {
                return;
            }

            $uses = NamespaceResolver::getUses($content);

            $content = $repository->replace($content);
            if (preg_match_all(
                '/^(?<before>(?<indent>\s*)[^\n]*)\$container->has\((?<name>[^$)\'"]+)\)\s*\?.*?;/ms',
                $content,
                $matches
            )) {
                foreach ($matches['name'] as $i => $class) {
                    $legacyName = $this->getLegacyName($class, $namespace, $uses);

                    $indent = strlen($matches['indent'][$i]);

                    $replace = $matches['before'][$i] . '$container->has(' . $class . ')' . PHP_EOL
                        . str_repeat(' ', $indent + 4) . '? $container->get(' . $class . ')' . PHP_EOL
                        . str_repeat(' ', $indent + 4) . ': ($container->has(\\' . $legacyName . ')' . PHP_EOL
                        . str_repeat(' ', $indent + 8) . '? $container->get(\\' . $legacyName . ')' . PHP_EOL
                        . str_repeat(' ', $indent + 8) . ': null);';

                    $content = str_replace($matches[0][$i], $replace, $content);
                }
            }

            if (preg_match_all(
                '/if\s*\(\$container->has\((?<name>[^$)\'"]+)\)\)\s*{\s*return\s*\$container->get\(/',
                $content,
                $matches
            )) {
                foreach ($matches['name'] as $i => $class) {
                    $legacyName = $this->getLegacyName($class, $namespace, $uses);

                    $replace = $matches[0][$i] . $class . ');' . PHP_EOL
                        . str_repeat(' ', 8) . '}' . PHP_EOL . PHP_EOL
                        . str_repeat(' ', 8) . 'if ($container->get(\\' . $legacyName . ')) {' . PHP_EOL
                        . str_repeat(' ', 12) . 'return $container->get(\\'
                        . str_replace($class, '', $legacyName);

                    $content = str_replace($matches[0][$i], $replace, $content);
                }
            }

            if (preg_match_all(
                '/if\s*\(!\s*\$container->has\(([^$)\'"]+)\)\)\s*{\s*throw/',
                $content,
                $matches
            )) {
                foreach ($matches[1] as $i => $class) {
                    $var = $this->getVariableName($class);
                    $legacyName = $this->getLegacyName($class, $namespace, $uses);

                    $replace = $var . ' = $container->has(' . $class . ')' . PHP_EOL
                        . str_repeat(' ', 12) . '? $container->get(' . $class . ')' . PHP_EOL
                        . str_repeat(' ', 12) . ': ($container->has(\\' . $legacyName . ')' . PHP_EOL
                        . str_repeat(' ', 16) . '? $container->get(\\' . $legacyName . ')' . PHP_EOL
                        . str_repeat(' ', 16) . ': null);' . PHP_EOL . PHP_EOL
                        . str_repeat(' ', 8) . 'if (' . $var . ' === null) {' . PHP_EOL
                        . str_repeat(' ', 12) . 'throw';

                    $placeholder = uniqid('___PLACEHOLDER___' . microtime(true), true);

                    $content = str_replace($matches[0][$i], $placeholder, $content);
                    $content = str_replace('$container->get(' . $class . ')', $var, $content);
                    $content = str_replace($placeholder, $replace, $content);
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

    private function getVariableName(string $class) : string
    {
        $name = str_replace(['::class', 'Interface'], '', $class);

        return '$' . lcfirst($name);
    }
}
