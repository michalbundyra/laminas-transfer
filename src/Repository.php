<?php

declare(strict_types=1);

namespace Laminas\Transfer;

use Laminas\ZendFrameworkBridge\RewriteRules;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;

use function array_diff;
use function array_merge;
use function date;
use function explode;
use function file_get_contents;
use function getcwd;
use function preg_quote;
use function str_replace;
use function strtr;

class Repository
{
    public const T_CONDUCT = 'CODE_OF_CONDUCT.md';
    public const T_CONTRIBUTING = 'CONTRIBUTING.md';
    public const T_COPYRIGHT = 'COPYRIGHT.md';
    public const T_LICENSE = 'LICENSE.md';
    public const T_SUPPORT = 'SUPPORT.md';

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
        'ZendService_' => 'Laminas_',
        'ZendService ' => 'Laminas ',
        'ZendTest\\\\Expressive' => 'ExpressiveTest',
        'ZendTest\\Expressive' => 'ExpressiveTest',
        'Zend\\\\Expressive' => 'Expressive',
        'Zend\\Expressive' => 'Expressive',
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
        'zf-component-installer' => 'laminas-component-installer',
        'zfcampus/zf-apigility' => 'apigility/apigility',
        'zfcampus/zf-' => 'apigility/apigility-',
        'zfcampus/' => 'apigility/',
        'ZF Apigility' => 'Apigility',
        'Zf-Apigility' => 'Apigility',
        'zf-apigility' => 'apigility',
        'zfapigility' => 'apigility',
        'zf-' => 'apigility-',
        'zend-problem-details' => 'expressive-problem-details',
        'zendframework/zend-expressive' => 'expressive/expressive',
        'zend-expressive' => 'expressive',
        // 'ZFTest\\\\Console' => 'LaminasTest\\\\ConsoleApplication',
        // 'ZFTest\\Console' => 'LaminasTest\\ConsoleApplication',
        'ZendTest\\\\ProblemDetails' => 'ExpressiveTest\\\\ProblemDetails',
        'ZendTest\\ProblemDetails' => 'ExpressiveTest\\ProblemDetails',
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
        // laminas-component-installer - support for "zf" and "laminas":
        'the zf-specific metadata' => 'the metadata',
        'return isset($extra[\'zf\']) && is_array($extra[\'zf\'])' . "\n" . '            ? $extra[\'zf\']'
            => 'if (isset($extra[\'laminas\']) && is_array($extra[\'laminas\'])) {'
            . "\n" . '            return $extra[\'laminas\'];'
            . "\n" . '        }'
            . "\n\n" . '        // supports legacy "extra.zf" configuration'
            . "\n" . '        return isset($extra[\'zf\']) && is_array($extra[\'zf\'])'
            . "\n" . '            ? $extra[\'zf\']',
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
        'Zend Framework 3' => 'Laminas',
        'Zend Framework 2' => 'Laminas',
        'Zend Framework' => 'Laminas',
        'ZendFramework' => 'Laminas',
        'Zend' => 'Laminas',
        'zend' => 'laminas',
        'ZF3' => 'Laminas',
        'ZF2' => 'Laminas',
        'Zf2' => 'Laminas',
        'zf2' => 'laminas',
        'ZF' => 'Laminas',
        'Zf' => 'Laminas',
        'zf' => 'laminas',
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

    public function getTemplateText(string $file) : string
    {
        $exp = explode('/', $this->getNewName(), 2);

        return strtr(file_get_contents(__DIR__ . '/../template/' . $file), [
            '{year}' => date('Y'),
            '{org}' => $exp[0],
            '{repo}' => $exp[1],
        ]);
    }
}
