<?php

declare(strict_types=1);

namespace LaminasTest\Transfer;

use Generator;
use Laminas\Transfer\Repository;
use PHPUnit\Framework\TestCase;

use function getcwd;

class RepositoryTest extends TestCase
{
    /**
     * @return string[]
     */
    public function name() : Generator
    {
        // ZendFramework
        yield 'zendframework/zend-mvc' => ['zendframework/zend-mvc', 'laminas/laminas-mvc'];
        yield 'zendframework/zendservice-twitter' => ['zendframework/zendservice-twitter', 'laminas/laminas-twitter'];

        // Expressive
        yield 'zendframework/zend-expressive' => ['zendframework/zend-expressive', 'mezzio/mezzio'];

        // Apigility
        yield 'zfcampus/zf-apigility' => ['zfcampus/zf-apigility', 'laminas-api-tools/api-tools'];
        yield 'zfcampus/zf-apigility-admin' => ['zfcampus/zf-apigility-admin', 'laminas-api-tools/api-tools-admin'];
        yield 'zfcampus/zf-hal' => ['zfcampus/zf-hal', 'laminas-api-tools/api-tools-hal'];
    }

    /**
     * @dataProvider name
     */
    public function testNewName(string $legacyName, string $newName) : void
    {
        $repository = new Repository($legacyName);

        self::assertSame($legacyName, $repository->getName());
        self::assertSame($newName, $repository->getNewName());
    }

    public function testDefaultPathIsScriptCurrentDir() : void
    {
        $currentDir = getcwd();
        $repository = new Repository('zendframework/laminas-transfer');

        self::assertSame($currentDir, $repository->getPath());
    }

    public function testUseCustomPath() : void
    {
        $repository = new Repository('zendframework/laminas-transfer', __DIR__);

        self::assertSame(__DIR__, $repository->getPath());
    }

    public function testDoesNotReplaceUrls() : void
    {
        $content = <<<'END'
class SomeClassname
{
    /**
     * Fixes zendframework/zend-mvc#42
     * @see https://github.com/zendframework/zend-view/issues/152
     */
    public function someMethod()
    {
    }
}
END;
        $repository = new Repository('zendframework/zend-view', __DIR__);
        $result = $repository->replace($content);
        self::assertSame($content, $result);
    }

    public function zendServerClasses() : array
    {
        return [
            'zend-cache Abstract Zend Server adapter' => [
                'zendframework/zend-cache',
                'Zend\Cache\Storage\Adapter\AbstractZendServer',
                'Laminas\Cache\Storage\Adapter\AbstractZendServer',
            ],
            'zend-cache Zend Server Disk adapter' => [
                'zendframework/zend-cache',
                'Zend\Cache\Storage\Adapter\ZendServerDisk',
                'Laminas\Cache\Storage\Adapter\ZendServerDisk',
            ],
            'zend-cache Zend Server Shm adapter' => [
                'zendframework/zend-cache',
                'Zend\Cache\Storage\Adapter\ZendServerShm',
                'Laminas\Cache\Storage\Adapter\ZendServerShm',
            ],
            'zend-log Zend Monitor writer' => [
                'zendframework/zend-log',
                'Zend\Log\Writer\ZendMonitor',
                'Laminas\Log\Writer\ZendMonitor',
            ],
        ];
    }

    /**
     * @dataProvider zendServerClasses
     */
    public function testDoesNotReplaceClassesNamedForZendServerFeatures(
        string $package,
        string $class,
        string $expected
    ) : void {
        $repository = new Repository($package, __DIR__);
        self::assertSame($expected, $repository->replace($class));
    }
}
