<?php

declare(strict_types=1);

namespace Laminas\Transfer\Command;

use Laminas\Transfer\Fixture\AbstractFixture;
use Laminas\Transfer\Fixture\ComposerFixture;
use Laminas\Transfer\Fixture\DocsFixture;
use Laminas\Transfer\Fixture\LicenseFixture;
use Laminas\Transfer\Fixture\SourceFixture;
use Laminas\Transfer\Repository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TransferCommand extends Command
{
    /** @var string[] */
    private $fixtures = [
        ComposerFixture::class,
        DocsFixture::class,
        LicenseFixture::class,
        SourceFixture::class,
    ];

    public function configure() : void
    {
        $this->setName('transfer')
             ->setDescription('Transfer ZF repository to Laminas Project')
             ->addArgument(
                 'repository',
                 InputArgument::REQUIRED,
                 'The repository name to transfer'
             );
    }

    public function execute(InputInterface $input, OutputInterface $output) : void
    {
        $repository = new Repository(
            $input->getArgument('repository'),
            'clone'
        );

        $output->writeln('<info>Transfer repository: ' . $repository->getName() . '</info>');

        // $repository->clone();

        foreach ($this->fixtures as $fixtureName) {
            /** @var AbstractFixture $fixture */
            $fixture = new $fixtureName($output);
            $fixture->process($repository);
        }
    }
}
