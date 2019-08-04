<?php

declare(strict_types=1);

namespace LaminasTest\Transfer\Fixture;

use Generator;
use Laminas\Transfer\Fixture\AbstractFixture;
use Laminas\Transfer\Repository;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Console\Output\Output;

use function array_filter;
use function basename;
use function glob;
use function preg_replace;
use function str_replace;
use function strpos;
use function strtolower;
use function strtr;
use function substr;

use const GLOB_ONLYDIR;

abstract class AbstractFixtureTest extends TestCase
{
    /** @var string */
    protected $name;

    /** @var vfsStreamDirectory */
    protected $root;

    /** @var ObjectProphecy|Output */
    protected $output;

    protected function setUp() : void
    {
        $this->name = strtr(static::class, [
            __NAMESPACE__ . '\\' => '',
            'FixtureTest' => '',
        ]);

        $this->root = vfsStream::setup();

        $this->output = $this->prophesize(Output::class);
    }

    public function projects() : Generator
    {
        $name = strtr(static::class, [
            __NAMESPACE__ . '\\' => '',
            'FixtureTest' => '',
        ]);
        $dirs = glob(__DIR__ . '/TestAsset/' . $name . '/*', GLOB_ONLYDIR);

        foreach ($dirs as $dir) {
            yield basename($dir) => [$dir];
        }
    }

    /**
     * @dataProvider projects
     */
    public function testFixture(string $dir) : void
    {
        $directory = vfsStream::copyFromFileSystem($dir, $this->root);
        $path = $directory->url();

        $name = $this->getRepositoryName(basename($dir));
        $repository = new Repository($name, $path);
        $files = array_filter($repository->files(), static function (string $file) : bool {
            return substr($file, -7) === '.result';
        });

        $fixtureClass = str_replace('Test', '', __NAMESPACE__) . '\\' . $this->name . 'Fixture';

        /** @var AbstractFixture $fixture */
        $fixture = new $fixtureClass($this->output->reveal());
        $fixture->process($repository);

        foreach ($files as $file) {
            self::assertFileEquals($file, substr($file, 0, -7));
        }
    }

    private function getRepositoryName(string $name) : string
    {
        $name = strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $name));

        return (strpos($name, 'zf-') === 0 ? 'zfcampus' : 'zendframework') . '/' . $name;
    }
}
