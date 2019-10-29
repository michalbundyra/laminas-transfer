<?php

declare(strict_types=1);

namespace Laminas\Transfer\Command;

use Laminas\Transfer\ThirdPartyRepository;
use stdClass;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function array_filter;
use function array_map;
use function array_shift;
use function chdir;
use function exec;
use function getcwd;
use function implode;
use function json_decode;
use function ltrim;
use function passthru;
use function preg_match;
use function sprintf;
use function strpos;
use function trim;

use const PHP_EOL;

class MigrateDepsOfDepsCommand extends Command
{
    private const DESCRIPTION = <<<'EOH'
Update project to require Laminas-variants of nested Zend Framework package dependencies.
EOH;

    private const HELP = <<<'EOH'
Sometimes, third-party packages will depend on Zend Framework packages.
In such cases, Composer will go ahead and install them if replacements
are not already required by the project.

This command will look for installed Zend Framework packages, and then
call the Composer binary to require the Laminas equivalents, using the
same constraints as previously used. As Laminas packages are marked as
replacements of their ZF equivalents, this will remove the ZF packages
as well.
EOH;

    public function configure() : void
    {
        $this->setName('migrate:nested-deps')
             ->setDescription(self::DESCRIPTION)
             ->setHelp(self::HELP)
             ->addArgument(
                 'path',
                 InputArgument::OPTIONAL,
                 'The path to the project/library to migrate',
                 getcwd()
             )
             ->addOption(
                 'composer',
                 'c',
                 InputOption::VALUE_REQUIRED,
                 'The path to the Composer binary, if not on your $PATH',
                 'composer'
             );
    }

    public function execute(InputInterface $input, OutputInterface $output) : int
    {
        $composer = $input->getOption('composer');
        $path = $input->getArgument('path');
        if ($path !== getcwd()) {
            chdir($path);
        }

        $output->writeln('<info>Checking for Zend Framework packages in project</info>');

        $command = sprintf('%s show -f json', $composer);
        $results = [];
        $status = 0;

        exec($command, $results, $status);

        if ($status !== 0) {
            $output->writeln(
                '<error>Unable to execute "composer show"; are you sure the path is correct?</error>'
            );
            return 1;
        }

        $data = json_decode(trim(implode("\n", $results)));
        $repo = new ThirdPartyRepository($path);
        $packages = [];

        foreach ($data->installed as $package) {
            if (! preg_match('#^(zfcampus|zendframework)/#', $package->name)) {
                continue;
            }

            $packages[] = $this->preparePackageInfo($package, $composer, $output);
        }

        if (! $packages) {
            $output->writeln('<info>No Zend Framework packages detected; nothing to do!</info>');
            return 0;
        }

        $createPackageSpec = static function (array $package) use ($repo) : string {
            return sprintf('"%s:~%s"', $repo->replace($package['name']), ltrim($package['version'], 'v'));
        };

        // Require root packages
        $success = $this->requirePackages(
            $output,
            $composer,
            array_map(
                $createPackageSpec,
                array_filter($packages, static function (array $package) {
                    return ! $package['dev'];
                })
            ),
            $forDev = false
        );

        // Require dev packages
        $success = $this->requirePackages(
            $output,
            $composer,
            array_map(
                $createPackageSpec,
                array_filter($packages, static function (array $package) {
                    return $package['dev'];
                })
            ),
            $forDev = true
        ) && $success;

        return $success ? 0 : 1;
    }

    /**
     * @return array {
     *     @var string $name
     *     @var string $version
     *     @var bool $dev
     * }
     */
    private function preparePackageInfo(stdClass $package, string $composer, OutputInterface $output) : array
    {
        return [
            'name' => $package->name,
            'version' => $package->version,
            'dev' => $this->isDevPackage($package->name, $composer, $output),
        ];
    }

    private function isDevPackage(string $packageName, string $composer, OutputInterface $output) : bool
    {
        $command = sprintf('%s why -r %s', $composer, $packageName);
        $results = [];
        $status = 0;

        exec($command, $results, $status);

        if ($status !== 0) {
            $output->writeln(sprintf(
                '<error>Error executing "%s"</error>',
                $command
            ));
            $output->writeln('<Info>Output:</error>');
            $output->writeln(implode(PHP_EOL, $results));
            return false;
        }

        $root = array_shift($results);
        if ($root && strpos($root, '(for development)') !== false) {
            return true;
        }

        return false;
    }

    private function requirePackages(OutputInterface $output, string $composer, array $packages, bool $forDev) : bool
    {
        if (! $packages) {
            // Nothing to do!
            return true;
        }

        $output->writeln(sprintf(
            '<info>Preparing to require the following packages%s:</info>',
            $forDev ? ' (for development)' : ''
        ));
        $output->writeln(implode("\n", array_map(static function ($package) {
            return sprintf('- %s', trim($package, '"'));
        }, $packages)));

        $command = sprintf(
            '%s require %s%s',
            $composer,
            $forDev ? '--dev ' : '',
            implode(' ', $packages)
        );
        passthru($command, $status);

        if ($status !== 0) {
            $output->writeln(sprintf(
                '<error>Error executing "%s"; please check the above logs for details</error>',
                $command
            ));
            return false;
        }

        return true;
    }
}
