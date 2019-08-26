<?php

declare(strict_types=1);

namespace Laminas\Transfer\Fixture;

use Exception;
use Laminas\Transfer\Helper\NamespaceResolver;
use Laminas\Transfer\Repository;

use function array_pop;
use function array_shift;
use function end;
use function explode;
use function file_get_contents;
use function file_put_contents;
use function implode;
use function in_array;
use function ltrim;
use function md5;
use function preg_match;
use function preg_match_all;
use function str_replace;
use function strlen;
use function strpos;
use function strstr;
use function substr;
use function trim;

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
            '/(?<before>\s*->withAttribute\(\s*)(?<name>[^:$\'")]+::class)\s*,/',
            $content,
            $matches
        )) {
            $results = $this->updateToEndOfStatement($matches, $content, true);
            foreach ($results as $row) {
                $legacyName = NamespaceResolver::getLegacyName($row['name'], $namespace, $uses);
                if ($legacyName === $repository->replace($legacyName)) {
                    continue;
                }

                $search = implode('', $row);
                $replace = $search . $row['before'] . '\\' . $legacyName . $row['after'];

                $content = str_replace($search, $replace, $content);
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
            '/(?<before>\s*\$[a-z>\s-]*->withAttribute\(\s*)(?<name>[^:$\'"]+::class)\s*,/m',
            $content,
            $matches
        )) {
            $results = $this->updateToEndOfStatement($matches, $content);
            foreach ($results as $row) {
                $legacyName = NamespaceResolver::getLegacyName($row['name'], $namespace, $uses);
                if ($legacyName === $repository->replace($legacyName)) {
                    continue;
                }

                $search = implode('', $row);
                $replace = $search . PHP_EOL
                    . $row['before'] . '\\' . $legacyName . $row['after'];

                if (preg_match('/(?<search>->willReturn\((?<name>.+?)\))\s*(->|;)/m', $search, $match)) {
                    $mock = trim(strstr($search, '->withAttribute(', true));

                    if (strpos($match['name'], $mock) === false) {
                        $replace = str_replace($match['search'], '->will([' . $mock . ', \'reveal\'])', $search)
                            . PHP_EOL
                            . $row['before'] . '\\' . $legacyName . $row['after'];
                    }
                }

                $content = str_replace($search, $replace, $content);
            }
        }

        file_put_contents($file, $content);
    }

    private function updateToEndOfStatement(array $matches, string $content, bool $breakOnEmpty = false) : array
    {
        $result = [];

        foreach ($matches['before'] as $i => $before) {
            if (! $breakOnEmpty) {
                $before = ltrim($before, "\n");
            }

            $delimiter = $before . $matches['name'][$i];
            $exp = explode($delimiter, $content);
            array_shift($exp);

            foreach ($exp as $part) {
                $end = $this->getEndOfStatementPosition($part, ['('], $breakOnEmpty);
                $after = substr($part, 0, $end + 1);

                $hash = md5($delimiter . $after);

                $result[$hash] = [
                    'before' => $before,
                    'name' => $matches['name'][$i],
                    'after' => $after,
                ];
            }
        }

        return $result;
    }

    /**
     * @throws Exception
     */
    private function getEndOfStatementPosition(string $string, array $stack, bool $breakOnEmpty = false) : int
    {
        $map = [')' => '(', ']' => '[', '}' => '{'];
        $max = strlen($string);
        for ($n = 0; $n < $max; ++$n) {
            $last = end($stack);
            switch ($string[$n]) {
                case '(':
                case '{':
                case '[':
                    if (! in_array($last, ['\'', '""'], true)) {
                        $stack[] = $string[$n];
                    }
                    break;
                case '\'':
                case '"':
                    if ($last === $string[$n]) {
                        array_pop($stack);
                    } elseif (! in_array($last, ['\'', '"'], true)) {
                        $stack[] = $string[$n];
                    }
                    break;
                case ')':
                case '}':
                case ']':
                    if ($last === $map[$string[$n]]) {
                        array_pop($stack);
                    } elseif (! $stack) {
                        return $n - 1;
                    }
                    break;
                case ';':
                    if (! $stack) {
                        return $n;
                    }
                    break;
            }

            if ($breakOnEmpty && ! $stack) {
                return $n;
            }
        }

        throw new Exception('Cannot find end of the statement!');
    }
}
