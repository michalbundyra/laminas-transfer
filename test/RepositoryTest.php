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
}
