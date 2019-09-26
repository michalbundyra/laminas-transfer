<?php

declare(strict_types=1);

namespace Laminas\Transfer\Fixture;

use Laminas\Transfer\Helper\NamespaceResolver;
use Laminas\Transfer\Repository;

use function array_merge;
use function current;
use function explode;
use function file_get_contents;
use function file_put_contents;
use function implode;
use function key;
use function next;
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
        // 'delegators', // we are not adding aliases for delegated services
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
        $files = array_merge(
            $repository->files('*/ConfigProvider.php'),
            $repository->files('*/Module.php'),
            $repository->files('config/*.php')
        );

        foreach ($files as $file) {
            $this->processFile($repository, $file);
        }
    }

    private function processFile(Repository $repository, string $file) : void
    {
        $content = file_get_contents($file);

        // No namespace detected
        if (! $namespace = NamespaceResolver::getNamespace($content)) {
            return;
        }

        $uses = NamespaceResolver::getUses($content);

        $offset = 0;
        $replacements = [];
        while (($result = $this->match($repository, $content, $offset, $uses, $namespace)) !== []) {
            $key = key($result);

            $offset = strpos($content, $key, $offset) + strlen($key);

            $key = $repository->replace($key);
            $replacements[$key] = current($result);
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
        $values = '(?:[^\[\]=>,]*?(?:=>\s*(?:[^\[\]]+?|\[[^\[\]]*?\]))?,?)*?';
        $section = '^(?<indent>\s*)(?:\'(?<key>' . implode('|', self::DI_KEYS) . ')\'\s*=>\s*\[\s*(?<content>.*?)\s*\]|\'delegators\'\s*=>\s*\[\s*' . $values . '\s*\]),?\n';
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
        $data = [];
        foreach ($matches['key'] as $i => $type) {
            $data[$type] = [
                'spaces' => strlen($matches['indent'][$i]),
                'content' => $matches['content'][$i],
            ];
            $this->aliases($matches['content'][$i], $aliases);
        }

        if (! $aliases) {
            return [];
        }

        $newContent = $repository->replace($content);
        if ($data['aliases'] ?? []) {
            $search = $repository->replace($data['aliases']['content']);
            $newData = $search;
            $spaces = $data['aliases']['spaces'] + 4;

            if (substr($newData, -1) !== ',') {
                $newData .= ',';
            }

            $newData .= PHP_EOL . PHP_EOL . str_repeat(' ', $spaces) . '// Legacy ZendFramework aliases';
            $newData .= $this->prepareData(
                $repository,
                $aliases,
                $spaces,
                $namespace,
                $uses
            );

            $newContent = str_replace($search, $newData, $newContent);
        } else {
            $spaces = current($data)['spaces'] ?? 0;

            $search = $repository->replace($matches[0][0]);
            $newData = str_repeat(' ', $spaces) . '// Legacy ZendFramework aliases' . PHP_EOL
                . str_repeat(' ', $spaces) . '\'aliases\' => [';
            $newData .= $this->prepareData(
                $repository,
                $aliases,
                $spaces + 4,
                $namespace,
                $uses
            );
            $newData .= PHP_EOL . str_repeat(' ', $spaces) . '],' . PHP_EOL;

            $newContent = str_replace($search, $newData . $search, $newContent);
        }

        return [$content => $newContent];
    }

    private function aliases(string $content, array &$aliases) : array
    {
        $lines = explode("\n", trim($content));
        while (($line = current($lines)) !== false) {
            if (strpos($line, '=>') === false) {
                $line .= '=>';
            }

            [$key, $value] = explode('=>', trim($line), 2);
            if (! $value) {
                $next = next($lines);

                if ($next === false || strpos(trim($next), '=>') !== 0) {
                    continue;
                }

                $value = substr(strstr($next, '=>'), 2);
            }

            $key = trim($key);
            $value = trim($value);

            if ($value === '[') {
                next($lines);
                continue;
            }

            if (strpos($key, '//') === 0) {
                next($lines);
                continue;
            }

            $aliases[] = $key;
            next($lines);
        }

        return $aliases;
    }

    private function prepareData(
        Repository $repository,
        array $aliases,
        int $spaces,
        string $namespace,
        array $uses
    ) : string {
        $data = '';

        foreach ($aliases as $alias) {
            if (strpos($alias, '\'') === 0
                || strpos($alias, '"') === 0
            ) {
                $newAlias = $repository->replace($alias);

                if ($newAlias !== $alias) {
                    $data .= PHP_EOL . str_repeat(' ', $spaces) . $alias . ' => ' . $newAlias . ',';
                }
            } elseif (strpos($alias, '::class') !== false) {
                $newKey = NamespaceResolver::getLegacyName($alias, $namespace, $uses);
                $newAlias = $repository->replace($newKey);

                if ($newAlias !== $newKey) {
                    $data .= PHP_EOL
                        . str_repeat(' ', $spaces)
                        . '\\' . $newKey
                        . ' => ' . $repository->replace($alias) . ',';
                }
            }
        }

        return $data;
    }
}
