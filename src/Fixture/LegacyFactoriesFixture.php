<?php

declare(strict_types=1);

namespace Laminas\Transfer\Fixture;

use Laminas\Transfer\Helper\NamespaceResolver;
use Laminas\Transfer\Repository;

use function file_get_contents;
use function file_put_contents;
use function preg_match;
use function preg_match_all;
use function sprintf;
use function str_repeat;
use function str_replace;
use function strtr;
use function trim;

use const PHP_EOL;

class LegacyFactoriesFixture extends AbstractFixture
{
    public function process(Repository $repository) : void
    {
        $files = $repository->files('*/*Factory.php');
        foreach ($files as $file) {
            $this->processFactory($repository, $file);
        }
        $repository->addReplacedContentFiles($files);

        $files = $repository->files('*/*FactoryTest.php');
        foreach ($files as $file) {
            $this->processFactoryTest($repository, $file);
        }
        $repository->addReplacedContentFiles($files);
    }

    private function processFactory(Repository $repository, string $file) : void
    {
        $content = file_get_contents($file);
        if (! $namespace = NamespaceResolver::getNamespace($content)) {
            return;
        }

        $uses = NamespaceResolver::getUses($content);

        $content = $repository->replace($content);
        // @phpcs:disable Generic.Files.LineLength.TooLong
        if (preg_match_all(
            '/^(?<before>(?<indent> *)[^\n]*)\$container->has\((?<name>[^$)\'"]+)\)\s*\?\s*\$container->get\(.*?\)\s*:\s*(?<else>.*?(\(\))?)(?<after>\s*\)?;)/ms',
            $content,
            $matches
        )) {
            // @phpcs:enable
            foreach ($matches['name'] as $i => $class) {
                $legacyName = NamespaceResolver::getLegacyName($class, $namespace, $uses);

                if ($legacyName === $repository->replace($legacyName)) {
                    continue;
                }

                $replace = preg_match('/(\&\&|\|\|)/', $matches['before'][$i])
                    ? $this->getReplacementForCompoundTernary(
                               $class,
                               $legacyName,
                               $matches['indent'][$i],
                               $matches['before'][$i],
                               $matches['else'][$i],
                               $matches['after'][$i]
                    )
                    : $this->getReplacementForTernary(
                        $class,
                        $legacyName,
                        $matches['indent'][$i],
                        $matches['before'][$i],
                        $matches['else'][$i],
                        $matches['after'][$i]
                    );

                $content = str_replace($matches[0][$i], $replace, $content);
            }
        }

        if (preg_match_all(
            '/(?<indent> *)if\s*\(\$container->has\((?<name>[^$)\'"]+)\)\)\s*{\s*return\s*\$container->get\(/',
            $content,
            $matches
        )) {
            foreach ($matches['name'] as $i => $class) {
                $legacyName = NamespaceResolver::getLegacyName($class, $namespace, $uses);

                if ($legacyName === $repository->replace($legacyName)) {
                    continue;
                }

                $indent = $matches['indent'][$i];

                $replace = $matches[0][$i] . $class . ');' . PHP_EOL
                    . $indent . '}' . PHP_EOL . PHP_EOL
                    . $indent . 'if ($container->has(\\' . $legacyName . ')) {' . PHP_EOL
                    . $indent . str_repeat(' ', 4) . 'return $container->get(\\'
                    . str_replace($class, '', $legacyName);

                $content = str_replace($matches[0][$i], $replace, $content);
            }
        }

        if (preg_match_all(
            '/(?<indent> *)if\s*\(!\s*\$container->has\((?<name>[^$)\'"]+)\)\)\s*{\s*throw/',
            $content,
            $matches
        )) {
            foreach ($matches['name'] as $i => $class) {
                $legacyName = NamespaceResolver::getLegacyName($class, $namespace, $uses);
                if ($legacyName === $repository->replace($legacyName)) {
                    continue;
                }

                $indent = $matches['indent'][$i];

                $replace = $indent
                    . 'if (! $container->has(' . $class . ')' . PHP_EOL
                    . $indent . str_repeat(' ', 4) . '&& ! $container->has(\\' . $legacyName . ')' . PHP_EOL
                    . $indent . ') {' . PHP_EOL
                    . $indent . str_repeat(' ', 4) . 'throw';

                $content = strtr($content, [
                    $matches[0][$i] => $replace,
                    '$container->get(' . $class . ')' => '$container->has(' . $class . ')'
                        . ' ? $container->get(' . $class . ')'
                        . ' : $container->get(\\' . $legacyName . ')',
                ]);
            }
        }

        file_put_contents($file, $content);
    }

    private function processFactoryTest(Repository $repository, string $file) : void
    {
        $content = file_get_contents($file);
        if (! $namespace = NamespaceResolver::getNamespace($content)) {
            return;
        }

        $uses = NamespaceResolver::getUses($content);

        $content = $repository->replace($content);

        // @phpcs:disable Generic.Files.LineLength.TooLong
        if (preg_match_all(
            '/^(?<before>\s*\$[a-z>-]+?->(has|get))\((?<name>[^$)\'"]+)\)->(?<after>shouldNotBeCalled\(\)|willReturn\(false\));/m',
            $content,
            $matches
        )) {
            // @phpcs:enable
            $pairs = [];
            foreach ($matches['name'] as $i => $class) {
                $legacyName = NamespaceResolver::getLegacyName($class, $namespace, $uses);

                if ($legacyName === $repository->replace($legacyName)) {
                    continue;
                }

                $replace = $matches[0][$i] . PHP_EOL
                    . $matches['before'][$i] . '(\\' . $legacyName . ')->' . $matches['after'][$i] . ';';

                $pairs[$matches[0][$i]] = $replace;
            }

            $content = strtr($content, $pairs);
        }

        file_put_contents($file, $content);
    }

    private function getReplacementForTernary(
        string $class,
        string $legacyName,
        string $indent,
        string $before,
        string $else,
        string $after
    ) : string {
        return $before . '$container->has(' . $class . ')' . PHP_EOL
            . $indent . str_repeat(' ', 4) . '? $container->get(' . $class . ')' . PHP_EOL
            . $indent . str_repeat(' ', 4) . ': ($container->has(\\' . $legacyName . ')' . PHP_EOL
            . $indent . str_repeat(' ', 8) . '? $container->get(\\' . $legacyName . ')' . PHP_EOL
            . $indent . str_repeat(' ', 8) . ': ' . $else . ')' . $after;
    }

    private function getReplacementForCompoundTernary(
        string $class,
        string $legacyName,
        string $indent,
        string $before,
        string $else,
        string $after
    ) : string {
        $before = str_replace($indent, '', $before);
        [$predicate, $condition] = $this->parseConditionalForPredicate($before);
        return sprintf(
            '%sif (%s$container->has(%s)) {' . PHP_EOL
            . '%s%s%s$container->get(%s);' . PHP_EOL
            . '%s} elseif (%s$container->has(\\%s)) {' . PHP_EOL
            . '%s%s%s$container->get(\\%s);' . PHP_EOL
            . '%s} else {' . PHP_EOL
            . '%s%s%s%s;' . PHP_EOL
            . '%s}%s',
            // Lines 1-2: if (condition + get new class) { predicate get(new class);}
            $indent,
            $condition,
            $class,
            $indent,
            str_repeat(' ', 4),
            $predicate,
            $class,
            // Lines 3-4: elseif (condition + get legacy class) { predicate get(legacy class);}
            $indent,
            $condition,
            $legacyName,
            $indent,
            str_repeat(' ', 4),
            $predicate,
            $legacyName,
            // Lines 5-6: else { predicate else;}
            $indent,
            $indent,
            str_repeat(' ', 4),
            $predicate,
            $else,
            // Line 7: end
            $indent,
            trim($after, ';')
        );
    }

    private function parseConditionalForPredicate(string $conditional) : array
    {
        if (preg_match('/^(?<predicate>[^=]+\s+\=\s+)(?<conditional>.*)$/', $conditional, $matches)) {
            return [$matches['predicate'], $matches['conditional']];
        }

        if (preg_match('/^return\s+(?<conditional>.*)$/', $conditional, $matches)) {
            return ['return ', $matches['conditional']];
        }

        return ['', $conditional];
    }
}
