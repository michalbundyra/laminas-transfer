<?php

declare(strict_types=1);

namespace Laminas\Transfer\Command;

use Laminas\Transfer\ThirdPartyRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function array_map;
use function chdir;
use function exec;
use function getcwd;
use function implode;
use function json_decode;
use function ltrim;
use function passthru;
use function preg_match;
use function sprintf;
use function trim;

class MigrateDepsOfDepsCommand extends Command
{
    public const DESCRIPTION = <<<'EOH'
Update project to require Laminas-variants of nested Zend Framework package dependencies.
EOH;

    public const HELP = <<<'EOH'
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

        if (0 !== $status) {
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

            $packages[] = sprintf('"%s:~%s"', $repo->replace($package->name), ltrim($package->version, 'v'));
        }

        if (empty($packages)) {
            $output->writeln('<info>No Zend Framework packages detected; nothing to do!</info>');
            return 0;
        }

        $output->writeln('<info>Preparing to require the following packages:</info>');
        $output->writeln(implode("\n", array_map(function ($package) {
            return sprintf('- %s', trim($package, '"'));
        }, $packages)));

        $command = sprintf('%s require %s', $composer, implode(' ', $packages));
        passthru($command, $status);

        if (0 !== $status) {
            $output->writeln(
                '<error>Error executing "composer require"; please check the above logs for details</error>'
            );
            return 1;
        }

        return 0;
    }
}
