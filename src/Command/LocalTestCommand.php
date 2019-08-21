<?php

declare(strict_types=1);

namespace Laminas\Transfer\Command;

use Generator;
use Github\Client;
use Laminas\Transfer\Repository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function chdir;
use function explode;
use function getcwd;
use function in_array;
use function system;

use const DIRECTORY_SEPARATOR;

class LocalTestCommand extends Command
{
    public function configure() : void
    {
        $this->setName('local-test')
             ->setDescription('Clone github repositories and rewrite latest version to run tests locally')
             ->addArgument('token', InputArgument::REQUIRED, 'GitHub token');
    }

    public function execute(InputInterface $input, OutputInterface $output) : void
    {
        $token = $input->getArgument('token');

        foreach ($this->repositories($token) as $repo) {
            $output->writeln('<info>' . $repo . '</info>');

            $repository = new Repository($repo);
            [$org, $name] = explode('/', $repository->getNewName());

            $dirname = getcwd() . DIRECTORY_SEPARATOR . $name;

            system('git clone https://github.com/' . $repo . ' ' . $dirname);

            $currentDir = getcwd();
            chdir($dirname);
            system(__DIR__ . '/../../bin/console rewrite ' . $repo . ' --local');
            chdir($currentDir);
        }
    }

    protected function repositories(string $token) : Generator
    {
        $client = new Client();
        $client->authenticate($token, null, $client::AUTH_URL_TOKEN);

        foreach (['zendframework', 'zfcampus'] as $org) {
            $page = 1;
            while (true) {
                $repos = $client->organization()->repositories($org, 'all', $page);
                ++$page;

                if (! $repos) {
                    break;
                }

                foreach ($repos as $repo) {
                    if (in_array($repo['name'], DependenciesCommand::SKIP, true)) {
                        continue;
                    }

                    yield $org . '/' . $repo['name'];
                }
            }
        }
    }
}
