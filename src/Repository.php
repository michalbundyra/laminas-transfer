<?php

declare(strict_types=1);

namespace Laminas\Transfer;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;

use function array_merge;
use function getcwd;
use function preg_quote;
use function str_replace;
use function strtr;
use function system;
use function trim;

class Repository
{
    /** @var string[] */
    private $replacements = [
        'Zend Technologies USA, Inc.' => 'Laminas',
        'www.zend.com' => 'laminas.dev',
        'zendframework.com' => 'laminas.dev',
        'zendframework' => 'laminas',
        'zend' => 'laminas',
        'zf' => 'laminas',
        'Zend Technologies USA Inc.' => 'Laminas',
        'Zend Framework' => 'Laminas',
        'ZendFramework' => 'Laminas',
        'Zend' => 'Laminas',
        'ZF' => 'Laminas',
    ];

    /** @var string */
    private $name;

    /** @var string */
    private $path;

    public function __construct(string $name, string $path)
    {
        $this->name = $name;
        $this->path = getcwd() . '/' . trim($path, '/');
    }

    public function getName() : string
    {
        return $this->name;
    }

    public function getNewName() : string
    {
        return $this->replace($this->name);
    }

    public function getPath() : string
    {
        return $this->path;
    }

    public function clone() : void
    {
        system('rm -Rf ' . $this->path);
        system('git clone https://github.com/' . $this->name . ' ' . $this->path);
    }

    public function files(string $pattern = '*') : array
    {
        $pattern = '/' . preg_quote($this->path . '/' . $pattern, '/') . '$/';
        $pattern = str_replace('\*', '.+', $pattern);

        $dir = new RecursiveDirectoryIterator(
            $this->path,
            RecursiveDirectoryIterator::SKIP_DOTS | RecursiveDirectoryIterator::UNIX_PATHS
        );
        $ite = new RecursiveIteratorIterator($dir);
        $files = new RegexIterator($ite, $pattern, RegexIterator::GET_MATCH);
        $fileList = [];

        foreach ($files as $file) {
            $fileList = array_merge($fileList, $file);
        }

        return $fileList;
    }

    public function replace(string $content) : string
    {
        return strtr($content, $this->replacements);
    }
}
