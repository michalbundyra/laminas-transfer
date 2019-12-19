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
use function next;
use function preg_match;
use function str_repeat;
use function str_replace;
use function strpos;
use function strstr;
use function substr;
use function trim;

use const PHP_EOL;

class PluginManagerFixture extends AbstractFixture
{
    public function process(Repository $repository) : void
    {
        $files = array_merge(
            $repository->files('*/*PluginManager.php'),
            $repository->files('*/*ManagerV2Polyfill.php'),
            $repository->files('*/*ManagerV3Polyfill.php'),
            $repository->files('*/DecoratorManager.php'), // zend-text
            $repository->files('*/HelperConfig.php') // zend-i18n
            // $repository->files('*/AssertionManager.php') // zend-permission-acl // nothing to rewrite
            // $repository->files('*/ControllerManager.php') // zend-mvc // nothing to rewrite
        );

        foreach ($files as $file) {
            $content = file_get_contents($file);
            if (! $namespace = NamespaceResolver::getNamespace($content)) {
                return;
            }

            $uses = NamespaceResolver::getUses($content);

            $newContent = $repository->replace($content);
            if (preg_match(
                '/\$factories\s*=\s*\[\s*(?<content>[^]]*?)\s*\];/ms',
                $content,
                $matches
            )) {
                $aliases = $this->aliases($matches['content']);

                $spaces = 8;
                $newData = '';
                foreach ($aliases as $alias => $value) {
                    if (strpos($alias, '\'') === 0
                        || strpos($alias, '"') === 0
                    ) {
                        $newAlias = $repository->replace($alias);

                        if ($newAlias !== $alias) {
                            $newData .= PHP_EOL . str_repeat(' ', $spaces) . $alias . ' => ' . $newAlias . ',';
                        }
                    } elseif (strpos($alias, '::class') !== false) {
                        $newKey = NamespaceResolver::getLegacyName($alias, $namespace, $uses);
                        $newAlias = $repository->replace($newKey);

                        if ($newAlias !== $newKey) {
                            $newData .= PHP_EOL
                                . str_repeat(' ', $spaces)
                                . '\\' . $newKey
                                . ' => ' . $alias . ',';
                        }
                    }
                }

                if (preg_match(
                    '/\$aliases\s*=\s*\[\s*(?<content>[^]]*?)\s*\];/ms',
                    $content,
                    $matchesAliases
                )) {
                    $search = $repository->replace($matchesAliases['content']);

                    $replace = $search;
                    if (substr($replace, -1) !== ',') {
                        $replace .= ',';
                    }
                    $replace .= PHP_EOL . PHP_EOL . str_repeat(' ', $spaces) . '// Legacy Zend Framework aliases';
                    $replace .= $newData;

                    $newContent = str_replace($search, $replace, $newContent);
                }
            }

            file_put_contents($file, $newContent);
        }

        $repository->addReplacedContentFiles($files);
    }

    private function aliases(string $content) : array
    {
        $aliases = [];

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

            if (substr($value, -1) === ',') {
                $value = substr($value, 0, -1);
            }

            $aliases[$key] = $value;
            next($lines);
        }

        return $aliases;
    }
}
