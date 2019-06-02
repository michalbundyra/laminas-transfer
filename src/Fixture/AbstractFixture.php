<?php

declare(strict_types=1);

namespace Laminas\Transfer\Fixture;

use Laminas\Transfer\Repository;
use Symfony\Component\Console\Output\Output;

abstract class AbstractFixture
{
    /** @var Output */
    protected $output;

    public function __construct(Output $output)
    {
        $this->output = $output;
    }

    abstract public function process(Repository $repository) : void;

    /**
     * @param iterable|string $messages
     */
    protected function write($messages, bool $newline = false, int $options = Output::OUTPUT_NORMAL) : void
    {
        $this->output->write($messages, $newline, $options);
    }

    /**
     * @param iterable|string $messages
     */
    protected function writeln($messages, int $options = Output::OUTPUT_NORMAL) : void
    {
        $this->output->writeln($messages, $options);
    }
}
