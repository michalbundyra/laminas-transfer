<?php

declare(strict_types=1);

namespace Laminas\Transfer\Command;

use Laminas\Transfer\Fixture\AbstractFixture;
use Laminas\Transfer\Fixture\DIAliasFixture;
use Laminas\Transfer\Fixture\LegacyFactoriesFixture;
use Laminas\Transfer\Fixture\LocalFixture;
use Laminas\Transfer\Fixture\MiddlewareAttributesFixture;
use Laminas\Transfer\Fixture\PluginManagerFixture;
use Laminas\Transfer\Fixture\SourceFixture;
use Laminas\Transfer\Fixture\ThirdPartyComposerFixture;
use Laminas\Transfer\Repository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Tool for updating a third-party library to target Laminas/Expressive/Apigility
 * directly, instead of using the laminas-zendframework-bridge.
 *
 * Updates:
 *
 * - The composer.json.
 * - Dependency configuration
 * - Middleware attribute references
 * - Source code imports
 */
class UpdateCommand extends Command
{
    /** @var string[] */
    private $fixtures = [
        ThirdPartyComposerFixture::class,
        DIAliasFixture::class,
        LegacyFactoriesFixture::class,
        PluginManagerFixture::class,
        MiddlewareAttributesFixture::class,
        SourceFixture::class,
    ];

    public function configure() : void
    {
        $this->setName('update')
             ->setDescription('Update a third-party library to target Laminas/Expressive/Apigility')
             ->addArgument(
                 'repository',
                 InputArgument::REQUIRED,
                 'The repository name to rewrite'
             )
             ->addOption(
                 'local',
                 'l',
                 InputOption::VALUE_NONE,
                 'Use local fixture to add repositories in composer.json (tests purposes)'
             );
    }

    public function execute(InputInterface $input, OutputInterface $output) : void
    {
        if ($input->getOption('local')) {
            $this->fixtures[] = LocalFixture::class;
        }

        $repository = new Repository(
            $input->getArgument('repository')
        );

        foreach ($this->fixtures as $fixtureName) {
            /** @var AbstractFixture $fixture */
            $fixture = new $fixtureName($output);
            $fixture->process($repository);
        }
    }
}
