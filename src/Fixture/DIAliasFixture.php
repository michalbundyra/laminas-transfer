<?php

declare(strict_types=1);

namespace Laminas\Transfer\Fixture;

use Laminas\Transfer\Helper\NamespaceResolver;
use Laminas\Transfer\Repository;

use function current;
use function explode;
use function file_get_contents;
use function file_put_contents;
use function implode;
use function key;
use function preg_match;
use function preg_match_all;
use function str_repeat;
use function str_replace;
use function strlen;
use function strpos;
use function strstr;
use function strtr;
use function substr;
use function trim;

use const PHP_EOL;

class DIAliasFixture extends AbstractFixture
{
    private const DI_KEYS = [
        'abstract_factories',
        'aliases',
        'delegators',
        'factories',
        'initializers',
        'invokables',
        'lazy_services',
        'services',
        'shared',
        'shared_by_default',
    ];

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

        $uses = NamespaceResolver::getUses($content);

        $offset = 0;
        $replacements = [];
        while (($result = $this->match($repository, $content, $offset, $uses, $namespace)) !== []) {
            $key = $repository->replace(key($result));
            $replacements[$key] = current($result);
            ++$offset;
        }

        $content = $repository->replace($content);
        $content = strtr($content, $replacements);
        file_put_contents($file, $content);
        $repository->addReplacedContentFiles([$file]);
    }

    private function match(Repository $repository, string $content, int $offset, array $uses, string $namespace) : array
    {
        // @phpcs:disable Generic.Files.LineLength.TooLong
        $comment = '(?:^\s*(?:\/\/|\*|\/\*).*?$\n)*';
        $section = '^(?<indent>\s*)\'(?<key>' . implode('|', self::DI_KEYS) . ')\'\s*=>\s*\[\s*(?<content>.*?)\s*\],?\n';
        $regexp = '/\[\s*(' . $comment . $section . ')+\s*\]/ms';
        // @phpcs:enable

        if (! preg_match($regexp, $content, $matches, 0, $offset)) {
            return [];
        }

        $content = $matches[0];

        if (! preg_match_all('/' . $section . '/ms', $content, $matches)) {
            return [];
        }

        $aliases = [];
        foreach ($matches['key'] as $i => $type) {
            $matches[$type][] = [
                'spaces' => strlen($matches['indent'][$i]),
                'content' => $matches['content'][$i],
            ];
            $this->aliases($matches['content'][$i], $aliases);
        }

        if (! $aliases) {
            return [];
        }

        $newContent = $repository->replace($content);
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

                    $name = str_replace('::class', '', $alias);
                    if (strpos($name, '\\') !== false) {
                        $name = strstr($name, '\\', true);
                    }

                    $newKey = isset($uses[$name]) ? $uses[$name] . '::class' : $namespace . '\\' . $alias;
                    $newAlias = $repository->replace($newKey);

                    if ($newAlias !== $newKey) {
                        $newData .= PHP_EOL
                            . str_repeat(' ', $spaces)
                            . '\\' . $newKey
                            . ' => ' . $repository->replace($alias) . ',';
                    }
                }
            }

            $newContent = str_replace($search, $newData, $newContent);
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

                    $name = str_replace('::class', '', $alias);
                    if (strpos($name, '\\') !== false) {
                        $name = strstr($name, '\\', true);
                    }

                    $newKey = isset($uses[$name]) ? $uses[$name] . '::class' : $namespace . '\\' . $alias;
                    $newAlias = $repository->replace($newKey);

                    if ($newAlias !== $newKey) {
                        $newData .= PHP_EOL
                            . str_repeat(' ', $spaces)
                            . '\\' . $newKey
                            . ' => ' . $repository->replace($alias) . ',';
                    }
                }
            }
            $newData .= PHP_EOL . '],' . PHP_EOL;

            $newContent = str_replace($search, $newData . $search, $newContent);
        }

        return [$content => $newContent];
    }

    private function aliases(string $content, array &$aliases) : array
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
