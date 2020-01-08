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
use Symfony\Component\Console\Question\ConfirmationQuestion;

use function chdir;
use function copy;
use function explode;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function getcwd;
use function mkdir;
use function preg_replace;
use function sprintf;
use function str_replace;
use function system;

use const DIRECTORY_SEPARATOR;

class DocsBuildWorkflowCommand extends Command
{
    public function configure() : void
    {
        $this->setName('docs-build-workflow')
             ->setDescription('Adds docs-build workflow to laminas and mezzio repos')
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
        $helper = $this->getHelper('question');

        $token = $input->getArgument('token');
        $client = new Client();
        $client->authenticate($token, null, $client::AUTH_URL_TOKEN);

        foreach ($this->repositories($client) as $repo) {
            $output->writeln('<info>' . $repo . '</info>');

            $path = $input->getOption('path');
            [$org, $name] = explode('/', $repo);

            $dirname = $path . DIRECTORY_SEPARATOR . $name;

            system('rm -Rf ' . $dirname);
            system('hub clone ' . $repo . ' ' . $dirname);

            $mkdocsFile = sprintf('%s/mkdocs.yml', $dirname);
            // Skip repositories without mkdocs configuration
            if (! file_exists($mkdocsFile)) {
                $output->writeln('<error>Skipping: ' . $repo . '</error>');
                continue;
            }

            $keys = $client->repo()->keys()->all($org, $name);
            if (empty($keys)) {
                system('rm -f ./docs-deploy-key-' . $org . '-' . $name . '*');
                system(
                    'ssh-keygen -t rsa -b 4096 -C "Actions DOCS_DEPLOY_KEY for ' . $repo . '" \
                    -f ./docs-deploy-key-' . $org . '-' . $name . ' -N ""'
                );

                $key = getcwd() . '/docs-deploy-key-' . $org . '-' . $name;
                $client->repo()->keys()->create($org, $name, [
                    'title' => 'Actions DOCS_DEPLOY_KEY',
                    'key' => file_get_contents($key . '.pub'),
                    'read_only' => false,
                ]);

                system('cat ' . $key . ' | pbcopy && open https://github.com/' . $repo . '/settings/secrets');
                $question = new ConfirmationQuestion('Is key added? Do you want continue? [Y/n] ', true);
                if (! $helper->ask($input, $output, $question)) {
                    $output->writeln('<error>Key not added! Skipping: ' . $repo . '</error>');
                    continue;
                }
            } else {
                $output->writeln('<comment>Key exists for repo ' . $repo . '. Skipping adding key.</comment>');
            }

            $license = file_get_contents($dirname . '/LICENSE.md');
            $newContent = str_replace('2019,', '2019-2020,', $license);
            if ($newContent !== $license) {
                file_put_contents($dirname . '/LICENSE.md', $newContent);
                system('cd ' . $dirname . ' && git add LICENSE.md');
            }

            $copyright = file_get_contents($dirname . '/COPYRIGHT.md');
            $newContent = str_replace('2019,', '2019-2020,', $copyright);
            if ($newContent !== $copyright) {
                file_put_contents($dirname . '/COPYRIGHT.md', $newContent);
                system('cd ' . $dirname . ' && git add COPYRIGHT.md');
            }

            system('cd ' . $dirname . ' && git commit -am "Update copyright year: 2020"');

            $content = file_get_contents($mkdocsFile);
            $newContent = str_replace('- index.md', '- Home: index.md', $content);
            if ($content !== $newContent) {
                file_put_contents($mkdocsFile, $newContent);
                system(
                    'cd ' . $dirname . ' && \
                        git add mkdocs.yml && \
                        git commit -am "Adds Home page title for index.md in mkdocs config"'
                );
            }
            $content = $newContent;
            $newContent = preg_replace('/^\s*project_url.*?$\n/m', '', $content);
            if ($newContent !== $content) {
                file_put_contents($mkdocsFile, $newContent);
                system(
                    'cd ' . $dirname . ' && \
                        git add mkdocs.yml && \
                        git commit -am "Removes redundant extra.project_url configuration"'
                );
            }

            mkdir($dirname . '/.github/workflows/', 0777, true);
            copy(
                __DIR__ . '/../../data/templates/workflows/docs-build.yml',
                $dirname . '/.github/workflows/docs-build.yml'
            );

            $currentDir = getcwd();
            chdir($dirname);

            $readmeFile = $dirname . '/README.md';
            if (file_exists($readmeFile)) {
                $content = file_get_contents($readmeFile);
                $newContent = str_replace('travis-ci.org', 'travis-ci.com', $content);

                if ($content !== $newContent) {
                    file_put_contents($readmeFile, $newContent);
                    system(
                        'cd ' . $dirname . ' && \
                        git add README.md && \
                        git commit -am "Updates build status badge: travis-ci.org -> travis-ci.com"'
                    );
                }
            }

            system(
                'cd ' . $dirname . ' && \
                git co -b qa/docs-build && \
                git add .github/workflows/docs-build.yml && \
                git commit -am "Adds github workflow to build documentation" && \
                git co master && \
                git merge --no-ff qa/docs-build -m "Merge branch \'qa/docs-build\'" &&
                git push origin master:master'
            );

            system(
                'cd ' . $dirname . ' && \
                git co develop && \
                git merge --no-ff qa/docs-build -m "Merge branch \'qa/docs-build\' into develop" && \
                git push origin develop:develop'
            );

            chdir($currentDir);

            // system(
            //     'curl --request POST "https://api.github.com/repos/' . $repo . '/dispatches" \
            //     -H "Authorization: token ' . $token . '" -H "Accept: application/vnd.github.everest-preview+json" \
            //     -H "Content-Type: application/json" -d \'{"event_type": "docs"}\''
            // );
        }

        return 0;
    }

    protected function repositories(Client $client) : Generator
    {
        foreach (['laminas'] as $org) {
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
