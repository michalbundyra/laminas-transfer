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
use function strpos;
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

    public function execute(InputInterface $input, OutputInterface $output) : int
    {
        $token = $input->getArgument('token');

        foreach ($this->repositories($token) as $repo) {
            $output->writeln('<info>' . $repo . '</info>');

            $repository = new Repository($repo);
            [$org, $name] = explode('/', $repository->getNewName());

            $dirname = getcwd() . DIRECTORY_SEPARATOR . $name;

            system('rm -Rf ' . $dirname);
            system('git clone https://github.com/' . $repo . ' ' . $dirname);
            system('rm -Rf ' . $dirname . '/.git');

            $currentDir = getcwd();
            chdir($dirname);
            system('cd ' . $dirname . ' && git init && git add . && git commit -am "original"');
            system(__DIR__ . '/../../bin/console rewrite ' . $repo);
            system(
                'cd ' . $dirname . ' && \
                composer config repositories.laminas composer https://laminas.mwop.net/repo/testing && \
                git add . && \
                git commit -am "' . $name . ': rewrite test 3"'
            );
            if (strpos($name, 'mezzio') === 0) {
                $org = 'mezzio-dev';
            } elseif (strpos($name, 'api-') === 0) {
                $org = 'laminas-api-tools-dev';
            } else {
                $org = 'laminas-dev';
            }
            system(
                'cd ' . $dirname . ' && \
                git remote add origin git@github.com:' . $org . '/' . $name . '.git && \
                git push origin --set-upstream master:master -f'
            );
            chdir($currentDir);
        }

        return 0;
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
