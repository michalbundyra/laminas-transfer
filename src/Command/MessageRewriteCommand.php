<?php

declare(strict_types=1);

namespace Laminas\Transfer\Command;

use Laminas\Transfer\Repository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function feof;
use function fread;
use function preg_replace;
use function stream_select;

use const STDIN;

class MessageRewriteCommand extends Command
{
    protected function configure() : void
    {
        $this->setName('rewrite:message');
        $this->setDescription('Rewrite a commit message for a repository');
        $this->addArgument(
            'repository',
            InputArgument::REQUIRED,
            'Name of the repository being rewritten'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $repo = $input->getArgument('repository');

        // stream_select uses pass-by-reference, so variables need to be created first
        $readStreams = [STDIN];
        $writeStreams = [];
        $errorStreams = [];
        $streamCount = stream_select($readStreams, $writeStreams, $errorStreams, 0);
        if ($streamCount !== 1) {
            $output->write('');
            return 0;
        }

        $content = '';
        while (! feof(STDIN)) {
            $content .= fread(STDIN, 1024);
        }

        $output->write($this->replace($content, new Repository($repo)), false, OutputInterface::OUTPUT_RAW);

        return 0;
    }

    private function replace(string $content, Repository $repo) : string
    {
        $replacement = '$1' . $repo->getName() . '$2';
        $content = preg_replace('/(^|[^a-zA-Z])(\#[1-9][0-9]*)/m', $replacement, $content);
        $content = preg_replace('/\[ZF2-\d+\]/', '', $content);

        return $repo->replace($content);
    }
}
