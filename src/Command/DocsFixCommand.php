<?php

declare(strict_types=1);

namespace Laminas\Transfer\Command;

use Generator;
use Github\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function chdir;
use function exec;
use function explode;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function getcwd;
use function implode;
use function preg_match;
use function preg_match_all;
use function preg_replace;
use function sprintf;
use function system;
use function trim;

use const DIRECTORY_SEPARATOR;
use const PHP_EOL;

class DocsFixCommand extends Command
{
    public function configure() : void
    {
        $this->setName('docs-fix')
             ->setDescription('Fix mkdocs.yml formatting and resolve merge conflicts master <-> develop')
             ->addArgument('token', InputArgument::REQUIRED, 'GitHub token')
             ->addOption(
                 'path',
                 'p',
                 InputOption::VALUE_REQUIRED,
                 'Path on which repositories should be checked out.',
                 getcwd()
             );
    }

    public function execute(InputInterface $input, OutputInterface $output) : int
    {
        $token = $input->getArgument('token');

        foreach ($this->repositories($token) as $repo) {
            $output->writeln('<info>' . $repo . '</info>');

            $path = $input->getOption('path');
            [$org, $name] = explode('/', $repo);

            $dirname = $path . DIRECTORY_SEPARATOR . $name;

            system('rm -Rf ' . $dirname);
            system('git clone https://github.com/' . $repo . ' ' . $dirname);

            $mkdocsFile = sprintf('%s/mkdocs.yml', $dirname);
            if (! file_exists($mkdocsFile)) {
                continue;
            }

            $content = file_get_contents($mkdocsFile);
            if (! preg_match_all('/^\s*(project(?:_url)?: .*?)$/sm', $content, $matches)) {
                continue;
            }

            $currentDir = getcwd();
            chdir($dirname);
            exec('cd ' . $dirname . ' && git show HEAD~1:mkdocs.yml', $result);
            $content = trim(implode(PHP_EOL, $result)) . PHP_EOL;

            preg_match('/^\s+/m', $content, $spaces);
            $indent = $spaces[0] ?? '  ';

            if (! preg_match('/^extra:$/m', $content)) {
                $content .= 'extra:' . PHP_EOL . $indent
                    . implode(PHP_EOL . $indent, $matches[1])
                    . PHP_EOL;
            } else {
                $content = preg_replace(
                    '/^extra:$(\s+)/m',
                    'extra:$1'
                    . implode('$1', $matches[1]) . '$1',
                    $content
                );
            }

            file_put_contents($mkdocsFile, $content);
            system(
                'cd ' . $dirname . ' && \
                git add mkdocs.yml && \
                git commit -am "Fixes formatting in mkdocs.yml" && \
                git push origin --set-upstream master:master'
            );
            chdir($currentDir);
        }

        return 0;
    }

    protected function repositories(string $token) : Generator
    {
        $client = new Client();
        $client->authenticate($token, null, $client::AUTH_URL_TOKEN);

        foreach (['laminas', 'mezzio'] as $org) {
            $page = 1;
            while (true) {
                $repos = $client->organization()->repositories($org, 'all', $page);
                ++$page;

                if (! $repos) {
                    break;
                }

                foreach ($repos as $repo) {
                    yield $org . '/' . $repo['name'];
                }
            }
        }
    }
}
