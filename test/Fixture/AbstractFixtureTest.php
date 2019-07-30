<?php

declare(strict_types=1);

namespace LaminasTest\Transfer\Fixture;

use Laminas\Transfer\Fixture\AbstractFixture;
use Laminas\Transfer\Repository;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Console\Output\Output;

use function str_replace;
use function strtr;
use function substr;

abstract class AbstractFixtureTest extends TestCase
{
    /** @var string */
    protected $name;

    /** @var string */
    protected $path;

    /** @var Repository */
    protected $repository;

    /** @var ObjectProphecy|Output */
    protected $output;

    /** @var string[] */
    private $originFiles = [];

    protected function setUp() : void
    {
        $this->name = strtr(static::class, [
            __NAMESPACE__ . '\\' => '',
            'FixtureTest' => '',
        ]);

        $root = vfsStream::setup();
        $directory = vfsStream::copyFromFileSystem(__DIR__ . '/TestAsset/' . $this->name, $root);
        $this->path = $directory->url();

        $this->repository = new Repository('zendframework/transfer', $this->path);

        $this->output = $this->prophesize(Output::class);

        $files = $this->repository->files();
        foreach ($files as $file) {
            if (substr($file, -7) === '.result') {
                continue;
            }
            $this->originFiles[] = $file;
        }
    }

    public function testFixture() : void
    {
        $fixtureClass = str_replace('Test', '', __NAMESPACE__) . '\\' . $this->name . 'Fixture';

        /** @var AbstractFixture $fixture */
        $fixture = new $fixtureClass($this->output->reveal());
        $fixture->process($this->repository);

        foreach ($this->originFiles as $file) {
            self::assertFileEquals($file . '.result', $file);
        }
    }
}
