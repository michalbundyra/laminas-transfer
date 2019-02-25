<?php

declare(strict_types=1);

namespace Laminas\Transfer\Command;

use Github\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function array_filter;
use function curl_close;
use function curl_exec;
use function curl_getinfo;
use function curl_init;
use function curl_setopt;
use function file_get_contents;
use function in_array;
use function json_decode;
use function sprintf;
use function strlen;
use function strpos;
use function substr;
use function var_export;

use const ARRAY_FILTER_USE_KEY;
use const CURLINFO_HTTP_CODE;
use const CURLOPT_NOBODY;

class DependenciesCommand extends Command
{
    /** @var array[] */
    private $repositories = [];

    /** @var string[] */
    private $resolved = [];

    /** @var string[] */
    private $resolving = [];

    public function configure() : void
    {
        $this->setName('dependencies')
             ->setDescription('Resolve package dependencies of the organisation')
             ->addArgument('org', InputArgument::REQUIRED, 'Organisation name')
             ->addArgument('token', InputArgument::REQUIRED, 'GitHub token');
    }

    public function execute(InputInterface $input, OutputInterface $output) : void
    {
        $org = $input->getArgument('org');
        $token = $input->getArgument('token');

        $client = new Client();
        $client->authenticate($token, null, $client::AUTH_URL_TOKEN);

        $skip = ['zendframework', 'zf1-extras', 'zf1', 'subsplit-ng', 'subsplit'];

        $page = 1;
        while (true) {
            $repos = $client->organization()->repositories($org, 'all', $page);
            ++$page;

            if (! $repos) {
                break;
            }

            foreach ($repos as $repo) {
                if (in_array($repo['name'], $skip, true)) {
                    continue;
                }

                $file = sprintf(
                    'https://raw.githubusercontent.com/%s/%s/master/composer.json',
                    $org,
                    $repo['name']
                );

                if (! $this->exists($file)) {
                    continue;
                }

                $this->repositories[$repo['name']] = json_decode(file_get_contents($file), true);
            }
        }

        foreach ($this->repositories as $name => $composer) {
            $this->resolveDependency($org, $name);
        }

        $output->writeln(var_export($this->resolved, true));
    }

    private function exists(string $url) : bool
    {
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_exec($ch);
        $retcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $retcode === 200;
    }

    private function resolveDependency(string $org, string $name) : void
    {
        static $indent = 0;
        ++$indent;

        if (in_array($name, $this->resolved, true)) {
            // already resolved
            return;
        }

        if (in_array($name, $this->resolving, true)) {
            return;
        }

        $this->resolving[] = $name;

        $dependencies = ($this->repositories[$name]['require'] ?? [])
            + ($this->repositories[$name]['require-dev'] ?? []);

        $dependencies = array_filter($dependencies, function ($name) use ($org) {
            return strpos($name, $org . '/') === 0;
        }, ARRAY_FILTER_USE_KEY);

        foreach ($dependencies as $dependency => $version) {
            $dep = substr($dependency, strlen($org) + 1);

            $this->resolveDependency($org, $dep);
        }

        $this->resolved[] = $name;
        --$indent;
    }
}
