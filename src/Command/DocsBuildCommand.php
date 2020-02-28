<?php

declare(strict_types=1);

namespace Laminas\Transfer\Command;

use Generator;
use Github\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function exec;
use function explode;
use function strpos;

class DocsBuildCommand extends Command
{
    public function configure() : void
    {
        $this->setName('docs-build')
             ->setDescription('Trigger documentation build for the given repository or organisation')
             ->addArgument('token', InputArgument::REQUIRED, 'GitHub token')
             ->addArgument('name', InputArgument::REQUIRED, 'Repository or organisation name');
    }

    public function execute(InputInterface $input, OutputInterface $output) : int
    {
        $token = $input->getArgument('token');
        $name = $input->getArgument('name');

        $org = null;
        $repo = null;

        if (strpos($name, '/') === false) {
            $org = $name;
        } else {
            [$org, $repo] = explode('/', $input->getArgument('name'));
        }

        if ($repo) {
            $output->writeln('Trigger documentation build for <info>' . $name . '</info>');
            $result = $this->triggerDocumentationBuild($token, $name);
            $output->writeln($result);
        } else {
            $output->writeln('<comment>Trigger documentation build for org </comment><info>' . $org . '</info>');
            foreach ($this->repositories($token, $org) as $fullRepoName) {
                $result = $this->triggerDocumentationBuild($token, $fullRepoName);
                $output->writeln($result);
            }
        }

        return 0;
    }

    private function triggerDocumentationBuild(string $token, string $repo) : array
    {
        exec(
            'curl --request POST "https://api.github.com/repos/' . $repo . '/dispatches" \
            -H "Authorization: token ' . $token . '" -H "Accept: application/vnd.github.everest-preview+json" \
            -H "Content-Type: application/json" -d \'{"event_type": "docs-build"}\'',
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
