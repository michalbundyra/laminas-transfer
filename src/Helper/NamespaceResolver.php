<?php

declare(strict_types=1);

namespace Laminas\Transfer\Helper;

use function explode;
use function preg_match;
use function preg_match_all;
use function str_replace;
use function strpos;
use function strrpos;
use function substr;

class NamespaceResolver
{
    public static function getNamespace(string $content) : ?string
    {
        preg_match('/<\?php.+?^namespace\s+(.+?);/ms', $content, $namespace);

        return $namespace[1] ?? null;
    }

    public static function getUses(string $content) : array
    {
        preg_match_all('/^use\s+(.*?)(?:\s*as\s*(.+?))?;/ms', $content, $usesMatches);
        $uses = [];
        foreach ($usesMatches[1] ?? [] as $i => $match) {
            $uses[$usesMatches[2][$i] ?: substr($match, strrpos($match, '\\') + 1)] = $match;
        }

        return $uses;
    }

    public static function getLegacyName(string $class, string $namespace, array $uses) : string
    {
        $class = str_replace('Laminas', 'Zend', $class);
        $className = str_replace('::class', '', $class);
        if (strpos($className, '\\') !== false) {
            [$className, $rest] = explode('\\', $className, 2);

            if (isset($uses[$className])) {
                return $uses[$className] . '\\' . $rest . '::class';
            }
        }

        return isset($uses[$className]) ? $uses[$className] . '::class' : $namespace . '\\' . $class;
    }
}
