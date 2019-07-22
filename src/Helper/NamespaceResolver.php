<?php

declare(strict_types=1);

namespace Laminas\Transfer\Helper;

use function preg_match;
use function preg_match_all;
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
}
