<?php

declare(strict_types=1);

namespace Laminas\Transfer;

use Laminas\ZendFrameworkBridge\RewriteRules;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;

use function array_diff;
use function array_merge;
use function getcwd;
use function preg_quote;
use function str_replace;
use function strtr;

class Repository
{
    /** @var string[] */
    private $replacements = [
        'ZendXml;' => 'Laminas\\Xml;',
        'ZendXml\\\\' => 'Laminas\\\\Xml\\\\',
        'ZendXmlTest\\\\' => 'LaminasTest\\\\Xml\\\\',
        'ZendXmlTest' => 'LaminasTest\\Xml',
        'ZendXml' => 'laminas-xml',
        'zendxml' => 'laminas-xml',
        '"ZendOAuth":' => '"Laminas\\\\OAuth":',
        'ZendOAuth;' => 'Laminas\\OAuth;',
        'ZendOAuthTest' => 'LaminasTest\\OAuth',
        'ZendOAuth' => 'laminas-oauth',
        'zendoauth' => 'laminas-oauth',
        'ZendDiagnostics;' => 'Laminas\\Diagnostics;',
        'ZendDiagnostics\\\\' => 'Laminas\\\\Diagnostics\\\\',
        'ZendDiagnosticsTest\\\\' => 'LaminasTest\\\\Diagnostics\\\\',
        'ZendDiagnosticsTest' => 'LaminasTest\\Diagnostics',
        'ZendDiagnostics' => 'zend-diagnostics',
        'zenddiagnostics' => 'zend-diagnostics',
        'ZendService_Amazon' => 'laminas-amazon',
        'ZendService_Apple_Apns' => 'laminas-apple-apns',
        'ZendService_Google_Gcm' => 'laminas-google-gcm',
        'ZendService_Twitter' => 'laminas-twitter',
        'zendservice-' => 'laminas-',
        'zfcampus/zf-development-mode' => 'laminas/laminas-development-mode',
        'zf-development-mode' => 'laminas-development-mode',
        'zfcampus/zf-deploy' => 'laminas/laminas-deploy',
        'zf-deploy' => 'laminas-deploy',
        // @todo: need to determine the final name for zf-console
        // 'zfcampus/zf-console' => 'laminas/laminas-console-application',
        // 'zf-console' => 'laminas-console-application',
        'zfcampus/zf-composer-autoloading' => 'laminas/laminas-composer-autoloading',
        'zf-composer-autoloading' => 'laminas-composer-autoloading',
        'zfcampus/zf-apigility' => 'apigility/apigility',
        'zfcampus/zf-' => 'apigility/apigility-',
        'zfcampus/' => 'apigility/',
        'zend-problem-details' => 'expressive-problem-details',
        'zendframework/zend-expressive' => 'expressive/expressive',
        'zend-expressive' => 'expressive',
        // 'ZFTest\\\\Console' => 'LaminasTest\\\\ConsoleApplication',
        // 'ZFTest\\Console' => 'LaminasTest\\ConsoleApplication',
        'ZendTest\\\\ProblemDetails' => 'ExpressiveTest\\\\ProblemDetails',
        'ZendTest\\ProblemDetails' => 'ExpressiveTest\\ProblemDetails',
        'ZendTest\\\\Expressive' => 'ExpressiveTest',
        'ZendTest\\Expressive' => 'ExpressiveTest',
        'ZFTest\\\\ComposerAutoloading' => 'LaminasTest\\\\ComposerAutoloading',
        'ZFTest\\ComposerAutoloading' => 'LaminasTest\\ComposerAutoloading',
        'ZFTest\\\\Deploy' => 'LaminasTest\\\\Deploy',
        'ZFTest\\Deploy' => 'LaminasTest\\Deploy',
        'ZFTest\\\\DevelopmentMode' => 'LaminasTest\\\\DevelopmentMode',
        'ZFTest\\DevelopmentMode' => 'LaminasTest\\DevelopmentMode',
        'ZFTest\\\\Apigility' => 'ApigilityTest',
        'ZFTest\\Apigility' => 'ApigilityTest',
        'ZFTest\\' => 'ApigilityTest\\',
        'ZendServiceTest' => 'LaminasTest',
        // expressive documentation
        'router/zf2.md' => 'router/laminas-router.md',
        ' as Zf2Bridge;' => ';',
        'Zf2Bridge' => 'LaminasRouter',
        'https://packages.zendframework.com/' => 'https://getlaminas.org/',
        'https://packages.zendframework.com' => 'https://getlaminas.org/',
        'http://packages.zendframework.com/' => 'https://getlaminas.org/',
        'http://packages.zendframework.com' => 'https://getlaminas.org/',
        'zendframework.github.io' => 'docs.laminas.dev',
        'zendframework/zf2/wiki/Coding-Standards' => 'laminas/laminas-coding-standard',
        'framework.zend.com/manual/current/en/index.html#' => 'docs.laminas.dev/',
        'framework.zend.com/manual/current/en/index.html' => 'docs.laminas.dev',
        'ZEND_' => 'LAMINAS_',
        'Zend Technologies USA, Inc.' => 'Laminas',
        'Zend Technologies USA Inc.' => 'Laminas',
        'www.zend.com' => 'laminas.dev',
        'zend.com' => 'getlaminas.org',
        'zendframework.com' => 'laminas.dev',
        'zendframework' => 'laminas',
        'zend' => 'laminas',
        'zf2' => 'laminas',
        'zf' => 'laminas',
        'Zend Framework' => 'Laminas',
        'ZendFramework' => 'Laminas',
        'Zend' => 'Laminas',
        'ZF2' => 'Laminas',
        'Zf2' => 'Laminas',
        'ZF' => 'Laminas',
    ];

    /** @var string */
    private $name;

    /** @var string */
    private $path;

    /** @var string[] */
    private $skippedFiles = [];

    public function __construct(string $name)
    {
        $this->name = $name;
        $this->path = getcwd();

        $namespaceRewrite = RewriteRules::namespaceRewrite();
        // In some places we have namespaces with double slashes - like composer.json
        $doubleSlashes = [];
        foreach ($namespaceRewrite as $legacy => $new) {
            $doubleSlashes[str_replace('\\', '\\\\', $legacy)] = str_replace('\\', '\\\\', $new);
        }
        $this->replacements = $doubleSlashes + $namespaceRewrite + $this->replacements;
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

    public function files(string $pattern = '*', bool $withSkipped = false) : array
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

        if ($withSkipped) {
            return $fileList;
        }

        return array_diff($fileList, $this->skippedFiles);
    }

    public function replace(string $content) : string
    {
        return strtr($content, $this->replacements);
    }

    public function addSkippedFiles(array $files) : void
    {
        $this->skippedFiles = array_merge($this->skippedFiles, $files);
    }
}
