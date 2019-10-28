<?php

declare(strict_types=1);

namespace Laminas\Transfer\Command;

use Laminas\Transfer\Fixture\AbstractFixture;
use Laminas\Transfer\Fixture\LocalFixture;
use Laminas\Transfer\Fixture\SourceFixture;
use Laminas\Transfer\Fixture\ThirdPartyComposerFixture;
use Laminas\Transfer\ThirdPartyRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function getcwd;

/**
 * Tool for migrating a third-party library or project to target
 * Laminas/Expressive/Apigility directly, instead of using the
 * laminas-zendframework-bridge.
 *
 * Updates:
 *
 * - The composer.json.
 * - Source code imports/references
 */
class MigrateCommand extends Command
{
    /** @var string[] */
    private $fixtures = [
        ThirdPartyComposerFixture::class,
        SourceFixture::class,
    ];

    public function configure() : void
    {
        $this->setName('migrate')
             ->setDescription('Migrate a project or third-party library to target Laminas/Expressive/Apigility')
             ->addArgument(
                 'path',
                 InputArgument::OPTIONAL,
                 'The path to the project/library to migrate',
                 getcwd()
             )
             ->addOption(
                 'name',
                 'r',
                 InputOption::VALUE_REQUIRED,
                 'The repository name to migrate'
             )
             ->addOption(
                 'no-install-dependency-plugin',
                 'p',
                 InputOption::VALUE_NONE,
                 'Do not install laminas/laminas-dependency-plugin'
             )
             ->addOption(
                 'local',
                 'l',
                 InputOption::VALUE_NONE,
                 'Use local fixture to add repositories in composer.json (test purposes)'
             );
    }

    public function execute(InputInterface $input, OutputInterface $output) : void
    {
        if ($input->getOption('local')) {
            $this->fixtures[] = LocalFixture::class;
        }

        $repository = new ThirdPartyRepository(
            $input->getArgument('path'),
            $input->getOption('name'),
            ! (bool) $input->getOption('no-install-dependency-plugin')
        );

        foreach ($this->fixtures as $fixtureName) {
            /** @var AbstractFixture $fixture */
            $fixture = new $fixtureName($output);
            $fixture->process($repository);
        }
    }
}
