<?php

declare(strict_types=1);

namespace Laminas\Transfer\Fixture;

use Laminas\Transfer\Helper\NamespaceResolver;
use Laminas\Transfer\Repository;

use function current;
use function explode;
use function file_get_contents;
use function file_put_contents;
use function preg_match_all;
use function str_repeat;
use function str_replace;
use function strlen;
use function strpos;
use function strstr;
use function substr;
use function trim;

use const PHP_EOL;

class DIAliasFixture extends AbstractFixture
{
    public function process(Repository $repository) : void
    {
        $this->configProvider($repository);
    }

    private function configProvider(Repository $repository) : void
    {
        $file = current($repository->files('*/ConfigProvider.php'));
        if (! $file) {
            return;
        }

        $content = file_get_contents($file);

        // No namespace detected
        if (! $namespace = NamespaceResolver::getNamespace($content)) {
            return;
        }

        // @todo: update all other keys: aliases, invokables, factories, ....
        if (! preg_match_all('/^(\s*)\'(aliases|invokables|factories)\'\s*=>\s*\[\s*(.*?)\s*\]/ms', $content, $matches)) {
            return;
        }

        $uses = NamespaceResolver::getUses($content);

        $aliases = [];
        foreach ($matches[2] as $i => $type) {
            $matches[$type][] = [
                'spaces' => strlen($matches[1][$i]),
                'content' => $matches[3][$i],
            ];
            $this->aliases($type, $matches[3][$i], $aliases);
        }

        if (! $aliases) {
            return;
        }

        $content = $repository->replace($content);
        foreach ($matches['aliases'] ?? [] as $data) {
            $search = $repository->replace($data['content']);
            $newData = $search;
            $spaces = $data['spaces'] + 4;

            if (substr($newData, -1) !== ',') {
                $newData .= ',';
            }

            $newData .= PHP_EOL . PHP_EOL . str_repeat(' ', $spaces) . '// Legacy ZendFramework aliases';

            foreach ($aliases as $alias) {
                if (strpos($alias, '\'') === 0
                    || strpos($alias, '"') === 0
                ) {
                    $newAlias = $repository->replace($alias);

                    if ($newAlias !== $alias) {
                        $newData .= PHP_EOL . str_repeat(' ', $spaces) . $alias . ' => ' . $newAlias . ',';
                    }
                } else {
                    if (strpos($alias, '::class') === false) {
                        continue;
                    }

                    $t = str_replace('::class', '', $alias);
                    if (strpos($t, '\\') !== false) {
                        $t = strstr($t, '\\', true);
                    }

                    $x = isset($uses[$t]) ? $uses[$t] . '::class' : $namespace . '\\' . $alias;
                    $newAlias = $repository->replace($x);

                    if ($newAlias !== $x) {
                        $newData .= PHP_EOL . str_repeat(' ', $spaces) . '\\' . $x . ' => ' . $repository->replace($alias) . ',';
                    }
                }
            }

            $content = str_replace($search, $newData, $content);
        }

        if (empty($matches['aliases'])) {
            $search = $repository->replace($matches[0][0]);
            $newData = '\'aliases\' => [';

            $spaces = 4;

            foreach ($aliases as $alias) {
                if (strpos($alias, '\'') === 0
                    || strpos($alias, '"') === 0
                ) {
                    $newAlias = $repository->replace($alias);

                    if ($newAlias !== $alias) {
                        $newData .= PHP_EOL . str_repeat(' ', $spaces) . $alias . ' => ' . $newAlias . ',';
                    }
                } else {
                    // skip constants, as these have replaced values
                    if (strpos($alias, '::class') === false) {
                        continue;
                    }

                    $t = str_replace('::class', '', $alias);
                    if (strpos($t, '\\') !== false) {
                        $t = strstr($t, '\\', true);
                    }

                    $x = isset($uses[$t]) ? $uses[$t] . '::class' : $namespace . '\\' . $alias;
                    $newAlias = $repository->replace($x);

                    if ($newAlias !== $x) {
                        $newData .= PHP_EOL . str_repeat(' ', $spaces) . '\\' . $x . ' => ' . $repository->replace($alias) . ',';
                    }
                }
            }
            $newData .= PHP_EOL . '],' . PHP_EOL;

            $content = str_replace($search, $newData . $search, $content);
        }

        file_put_contents($file, $content);
        $repository->addReplacedContentFiles([$file]);
    }

    private function aliases(string $type, string $content, array &$aliases) : array
    {
        $lines = explode("\n", trim($content));
        foreach ($lines as $line) {
            [$key, $value] = explode('=>', trim($line), 2);
            if (! $value) {
                continue;
            }

            $key = trim($key);
            $value = trim($value);

            if ($value === '[') {
                continue;
            }

            if (strpos($key, '//') === 0) {
                continue;
            }

            $aliases[] = $key;
        }

        return $aliases;
    }
}
