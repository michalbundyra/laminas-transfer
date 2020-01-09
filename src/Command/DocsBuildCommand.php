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

class DocsBuildCommand extends Command
{
    public function configure() : void
    {
        $this->setName('docs-build')
             ->setDescription('Trigger documentation build for the given repository')
             ->addArgument('token', InputArgument::REQUIRED, 'GitHub token')
             ->addOption(
                 'repo',
                 'r',
                 InputOption::VALUE_REQUIRED,
                 'Repository name to trigger documentation build'
             )
             ->addOption(
                 'org',
                 'o',
                 InputOption::VALUE_REQUIRED,
                 'Organisation name to trigger documentation build'
             );
    }

    public function execute(InputInterface $input, OutputInterface $output) : int
    {
        $token = $input->getArgument('token');

        if ($input->hasOption('repo')) {
            $repo = $input->getOption('repo');
            $output->writeln('Trigger documentation build for <info>' . $repo . '</info>');
            $result = $this->triggerDocumentationBuild($token, $repo);
            $output->writeln($result);
        } elseif ($input->hasOption('org')) {
            $org = $input->getOption('org');
            $output->writeln('<comment>Trigger documentation build for org </comment><info>' . $org . '</info>');
            foreach ($this->repositories($token, $org) as $repo) {
                $result = $this->triggerDocumentationBuild($token, $repo);
                $output->writeln($result);
            }
        } else {
            $output->writeln('<error>Provide repository or organisation name to trigger documentation build');
            return 1;
        }

        return 0;
    }

    private function triggerDocumentationBuild(string $token, string $repo): array
    {
        exec(
            'curl --request POST "https://api.github.com/repos/' . $repo . '/dispatches" \
            -H "Authorization: token ' . $token . '" -H "Accept: application/vnd.github.everest-preview+json" \
            -H "Content-Type: application/json" -d \'{"event_type": "docs"}\'',
            $output
        );

        return $output;
    }

    private function repositories(string $token, string $org) : Generator
    {
        $client = new Client();
        $client->authenticate($token, null, $client::AUTH_URL_TOKEN);

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
