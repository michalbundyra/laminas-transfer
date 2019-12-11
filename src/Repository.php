<?php

declare(strict_types=1);

namespace Laminas\Transfer;

use Laminas\ZendFrameworkBridge\RewriteRules;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;

use function array_flip;
use function array_merge;
use function date;
use function explode;
use function file_get_contents;
use function getcwd;
use function in_array;
use function is_dir;
use function preg_match_all;
use function preg_quote;
use function rename;
use function sprintf;
use function str_replace;
use function strtr;
use function system;

use const DIRECTORY_SEPARATOR;

class Repository
{
    public const T_CONDUCT = 'CODE_OF_CONDUCT.md';
    public const T_CONTRIBUTING = 'CONTRIBUTING.md';
    public const T_COPYRIGHT = 'COPYRIGHT.md';
    public const T_LICENSE = 'LICENSE.md';
    public const T_SUPPORT = 'SUPPORT.md';

    // @phpcs:disable
    public const REGEX_URL = '%\b(?P<url>(?:zendframework|zfcampus)/[^/]+(?:/(?:issues|pull)/|#)\d+)\b%i';
    // @phpcs:enable

    /** @var string[] */
    private $replacements = [
        // Do not rewrite
        'zf-commons' => 'zf-commons',
        'zfc-' => 'zfc-',
        'zfc_' => 'zfc_',
        'Zfc' => 'Zfc',
        'zfr_' => 'zfr_',
        'zfr/' => 'zfr/',
        'api-skeletons/zf-' => 'api-skeletons/zf-',
        'phpro/zf-' => 'phpro/zf-',
        'doctrine-zend' => 'doctrine-zend',
        // Repositories we are not moving:
        'zfcampus/zf-console' => 'zfcampus/zf-console',
        'zf-console' => 'zf-console',
        'ZFTest\\\\Console' => 'ZFTest\\\\Console',
        'ZFTest\\Console' => 'ZFTest\\Console',
        'ZendPdf' => 'ZendPdf',
        'Zend Pdf' => 'Zend Pdf',
        'zendframework/zendpdf' => 'zendframework/zendpdf',
        'Zend\\Version' => 'Zend\\Version',
        'zendframework/zend-version' => 'zendframework/zend-version',
        'zend-version' => 'zend-version',
        'Zend\\Debug' => 'Zend\\Debug',
        'zendframework/zend-debug' => 'zendframework/zend-debug',
        'zend-debug' => 'zend-debug',
        // Rewrite rules:
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
        'ZendDiagnostics' => 'laminas-diagnostics',
        'zenddiagnostics' => 'laminas-diagnostics',
        'ZendService_ReCaptcha' => 'laminas-recaptcha',
        'ZendService_Twitter' => 'laminas-twitter',
        'ZendService\\\\ReCaptcha' => 'Laminas\\\\ReCaptcha',
        'ZendService\\ReCaptcha' => 'Laminas\\ReCaptcha',
        'ZendService\\\\Twitter' => 'Laminas\\\\Twitter',
        'ZendService\\Twitter' => 'Laminas\\Twitter',
        'ZendTest\\\\ProblemDetails' => 'MezzioTest\\\\ProblemDetails',
        'ZendTest\\ProblemDetails' => 'MezzioTest\\ProblemDetails',
        'Zend\\\\ProblemDetails' => 'Mezzio\\\\ProblemDetails',
        'Zend\\ProblemDetails' => 'Mezzio\\ProblemDetails',
        'ZendTest\\\\Expressive' => 'MezzioTest',
        'ZendTest\\Expressive' => 'MezzioTest',
        'Zend\\\\Expressive' => 'Mezzio',
        'Zend\\Expressive' => 'Mezzio',
        'zf-mkdoc-theme' => 'laminas-mkdoc-theme',
        'zendservice-' => 'laminas-',
        'apigility.org' => 'api-tools.getlaminas.dev',
        'zfcampus/zf-development-mode' => 'laminas/laminas-development-mode',
        'zf-development-mode' => 'laminas-development-mode',
        'zfcampus/zf-deploy' => 'laminas/laminas-deploy',
        'zf-deploy' => 'laminas-deploy',
        'zfdeploy.php' => 'laminas-deploy',
        'zfdeploy.phar' => 'laminas-deploy.phar',
        'zfcampus/zf-composer-autoloading' => 'laminas/laminas-composer-autoloading',
        'zf-composer-autoloading' => 'laminas-composer-autoloading',
        'zf-component-installer' => 'laminas-component-installer',
        'zfcampus/zf-apigility' => 'laminas-api-tools/api-tools',
        'zfcampus/zf-' => 'laminas-api-tools/api-tools-',
        'zfcampus/' => 'laminas-api-tools/',
        'zf-apigility/' => 'api-tools/',
        'apigility/' => 'api-tools/',
        'ZF\\\\Apigility' => 'Laminas\\\\ApiTools',
        'ZF\\Apigility' => 'Laminas\\ApiTools',
        'ZF\\\\ComposerAutoloading' => 'Laminas\\\\ComposerAutoloading',
        'ZF\\ComposerAutoloading' => 'Laminas\\ComposerAutoloading',
        'ZF\\\\Deploy' => 'Laminas\\\\Deploy',
        'ZF\\Deploy' => 'Laminas\\Deploy',
        'ZF\\\\DevelopmentMode' => 'Laminas\\\\DevelopmentMode',
        'ZF\\DevelopmentMode' => 'Laminas\\DevelopmentMode',
        'ZFApigility' => 'LaminasApiTools',
        'ZfApigility' => 'LaminasApiTools',
        'ZF Apigility' => 'Laminas API Tools',
        'Zf-Apigility' => 'api-tools',
        'zf-apigility' => 'api-tools',
        'zfapigility' => 'apitools',
        'zf-' => 'api-tools-',
        'docs.zendframework.com/zend-expressive' => 'docs.mezzio.dev',
        'zendframework.github.io/zend-expressive' => 'docs.mezzio.dev',
        'docs.zendframework.com/zend-problem-details' => 'docs.mezzio.dev/mezzio-problem-details',
        'zendframework.github.io/zend-problem-details' => 'docs.mezzio.dev/mezzio-problem-details',
        'zendframework.github.io' => 'docs.laminas.dev',
        'zendframework/zend-problem-details' => 'mezzio/mezzio-problem-details',
        'zend-problem-details' => 'mezzio-problem-details',
        'zendframework/zend-expressive' => 'mezzio/mezzio',
        'zend-expressive' => 'mezzio',
        'ZFTest\\\\ComposerAutoloading' => 'LaminasTest\\\\ComposerAutoloading',
        'ZFTest\\ComposerAutoloading' => 'LaminasTest\\ComposerAutoloading',
        'ZFTest\\\\Deploy' => 'LaminasTest\\\\Deploy',
        'ZFTest\\Deploy' => 'LaminasTest\\Deploy',
        'ZFTest\\\\DevelopmentMode' => 'LaminasTest\\\\DevelopmentMode',
        'ZFTest\\DevelopmentMode' => 'LaminasTest\\DevelopmentMode',
        'ZFTest\\\\Apigility' => 'LaminasTest\\\\ApiTools',
        'ZFTest\\Apigility' => 'LaminasTest\\ApiTools',
        'ZFTest\\\\' => 'LaminasTest\\\\ApiTools\\\\',
        'ZFTest\\' => 'LaminasTest\\ApiTools\\',
        'ZendServiceTest' => 'LaminasTest',
        'Expressive' => 'Mezzio',
        'expressive' => 'mezzio',
        'router/zf2.md' => 'router/laminas-router.md',
        ' as Zf2Bridge;' => ';',
        'Zf2Bridge' => 'LaminasRouter',
        'https://packages.zendframework.com/' => 'https://getlaminas.org/',
        'https://packages.zendframework.com' => 'https://getlaminas.org/',
        'http://packages.zendframework.com/' => 'https://getlaminas.org/',
        'http://packages.zendframework.com' => 'https://getlaminas.org/',
        'http://www.mkdocs.org' => 'https://www.mkdocs.org',
        'zendframework-slack.herokuapp.com' => 'laminas.dev/chat',
        'discourse.zendframework.com' => 'discourse.laminas.dev',
        'https://secure.travis-ci.org' => 'https://travis-ci.org',
        'http://secure.travis-ci.org' => 'https://travis-ci.org',
        'http://travis-ci.org' => 'https://travis-ci.org',
        'zendframework/zf2/wiki/Coding-Standards' => 'laminas/laminas-coding-standard',
        'http://framework.zend.com/manual/current/en/modules/' => 'https://docs.laminas.dev/',
        'zendservice.re-captcha.html' => 'laminas-recaptcha',
        'zendservice.twitter.html' => 'laminas-twitter',
        'http://framework.zend.com/manual/current/en/index.html#' => 'https://docs.laminas.dev/',
        'http://framework.zend.com/manual/current/en/index.html' => 'https://docs.laminas.dev',
        'framework.zend.com/manual/current/en/index.html#' => 'docs.laminas.dev/',
        'framework.zend.com/manual/current/en/index.html' => 'docs.laminas.dev',
        'http://framework.zend.com' => 'https://getlaminas.org',
        'framework.zend.com' => 'getlaminas.org',
        'ZEND_' => 'LAMINAS_',
        'Zend Technologies USA, Inc.' => 'Laminas',
        'Zend Technologies USA Inc.' => 'Laminas',
        'http://www.zend.com' => 'https://getlaminas.org',
        'www.zend.com' => 'getlaminas.org',
        'zend.com' => 'getlaminas.org',
        'zendframework.com' => 'laminas.dev',
        'zendframework' => 'laminas',
        'zend-framework.flf' => 'laminas-project.flf',
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
        // DeveloperTools
        'ZendDeveloperTools tests suite' => 'Laminas\\DeveloperTools tests suite',
        '"ZendDeveloperTools"' => '"Laminas\\\\DeveloperTools"',
        '`ZendDeveloperTools`' => '`Laminas\\\\DeveloperTools`',
        'ZendDeveloperTools;' => 'Laminas\\DeveloperTools;',
        'ZendDeveloperTools\\\\' => 'Laminas\\\\DeveloperTools\\\\',
        'ZendDeveloperToolsTest\\\\' => 'LaminasTest\\\\DeveloperTools\\\\',
        'ZendDeveloperToolsTest' => 'LaminasTest\\DeveloperTools',
        'zenddevelopertools' => 'laminas-developer-tools',
        'ZfSnapEventDebugger' => 'ZfSnapEventDebugger',
        // fix typo
        'apigiltiy' => 'apigility',
    ];

    /** @var string */
    private $name;

    /** @var string */
    private $path;

    /** @var string[] */
    private $replacedContentFiles = [];

    /** @var bool */
    private $underGit;

    public function __construct(string $name, ?string $path = null)
    {
        $this->name = $name;
        $this->path = $path ?: getcwd();

        $this->underGit = is_dir($this->path . DIRECTORY_SEPARATOR . '.git');

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
        preg_match_all(self::REGEX_URL, $content, $matches);

        if (empty($matches['url'])) {
            return strtr($content, $this->replacements);
        }

        $urlMap = [];
        foreach ((array) $matches['url'] as $index => $url) {
            $replacement = sprintf('%%TRANSFER_URL_%d%%', $index);
            $urlMap[$replacement] = $url;
        }

        return strtr(
            strtr($content, array_flip($urlMap)),
            $this->replacements + $urlMap
        );
    }

    public function addReplacedContentFiles(array $files) : void
    {
        $this->replacedContentFiles = array_merge($this->replacedContentFiles, $files);
    }

    public function hasReplacedContent(string $file) : bool
    {
        return in_array($file, $this->replacedContentFiles, true);
    }

    public function getTemplateText(string $file) : string
    {
        return $this->replaceTemplatedText(__DIR__ . '/../data/templates/' . $file);
    }

    public function replaceTemplatedText(string $filename) : string
    {
        [$org, $repo] = explode('/', $this->getNewName(), 2);

        return strtr(file_get_contents($filename), [
            '{year}' => date('Y'),
            '{org}' => $org,
            '{repo}' => $repo,
        ]);
    }

    private function isUnderGit() : bool
    {
        return $this->underGit;
    }

    public function move(string $source, string $target) : void
    {
        if ($this->isUnderGit()) {
            system('git mv ' . $source . ' ' . $target);
        } else {
            rename($source, $target);
        }
    }
}
