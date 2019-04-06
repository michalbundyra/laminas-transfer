<?php

declare(strict_types=1);

namespace Laminas\Transfer\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function chdir;
use function date;
use function exec;
use function getcwd;
use function microtime;
use function preg_replace;
use function realpath;
use function sprintf;
use function strtolower;
use function system;

class TransferCommand extends Command
{
    public function configure() : void
    {
        $this->setName('transfer')
             ->setDescription('Transfer ZF repository to Laminas Project')
             ->addArgument(
                 'repository',
                 InputArgument::REQUIRED,
                 'The repository name to transfer'
             )
             ->addArgument(
                 'path',
                 InputArgument::REQUIRED,
                 'The path to use (for better performance is recommended to use ramdisk)'
             );
    }

    public function execute(InputInterface $input, OutputInterface $output) : void
    {
        $repository = $input->getArgument('repository');
        $path = $input->getArgument('path');

        $start = microtime(true);
        $output->writeln(sprintf('<info>Transfering repository %s</info>', $repository));

        $dirname = realpath($path) . '/'
            . preg_replace('/\W/', '-', $repository)
            . date('_Y-m-d_H-i-s');

        system('rm -Rf ' . $dirname);
        system('git clone https://github.com/' . $repository . ' ' . $dirname);

        $currentDir = getcwd();
        chdir($dirname);

        system(
            'for branch in `git branch -a | grep remotes | grep -v HEAD | grep -v master | grep -v gh-pages`; do
                git branch --track ${branch#remotes/origin/} $branch;
            done'
        );

        system(sprintf(
            'git filter-branch -f'
                . ' --tree-filter "php %s rewrite %s"'
                . ' --commit-filter \'git_commit_non_empty_tree "$@"\''
                . ' --tag-name-filter cat -- --all',
            __DIR__ . '/../../bin/console',
            $repository
        ));

        exec('git tag -l', $versions);
        foreach ($versions as $version) {
            $this->clearTag($version);
        }

        chdir($currentDir);

        $output->writeln(sprintf('<info>DONE in %0.4f minutes</info>', (microtime(true) - $start) / 60));
        $output->writeln('<comment>Directory:</comment> ' . $dirname);
    }

    private function clearTag(string $tag) : string
    {
        $newTag = preg_replace('/^.*?(\d+\.\d+\.\d+).*?(?:(alpha|beta|rc).*?(\d+))?$/i', '$1$2$3', $tag);
        $newTag = strtolower($newTag);

        if ($newTag !== $tag) {
            system('git tag ' . $newTag . ' ' . $tag);
            system('git tag -d ' . $tag);
        }

        return $newTag;
    }
}
