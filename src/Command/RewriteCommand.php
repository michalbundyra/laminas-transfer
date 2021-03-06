<?php

declare(strict_types=1);

namespace Laminas\Transfer\Command;

use Laminas\Transfer\Fixture\AbstractFixture;
use Laminas\Transfer\Fixture\ComposerFixture;
use Laminas\Transfer\Fixture\CustomFixture;
use Laminas\Transfer\Fixture\DIAliasFixture;
use Laminas\Transfer\Fixture\DocsFixture;
use Laminas\Transfer\Fixture\FunctionAliasFixture;
use Laminas\Transfer\Fixture\LegacyFactoriesFixture;
use Laminas\Transfer\Fixture\LicenseFixture;
use Laminas\Transfer\Fixture\MiddlewareAttributesFixture;
use Laminas\Transfer\Fixture\NamespacedConstantFixture;
use Laminas\Transfer\Fixture\PluginManagerFixture;
use Laminas\Transfer\Fixture\QAConfigFixture;
use Laminas\Transfer\Fixture\SourceFixture;
use Laminas\Transfer\Repository;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function current;
use function file_get_contents;
use function json_decode;
use function sprintf;

class RewriteCommand extends Command
{
    /** @var string[] */
    private $fixtures = [
        FunctionAliasFixture::class,
        NamespacedConstantFixture::class,
        ComposerFixture::class,
        DocsFixture::class,
        LicenseFixture::class,
        QAConfigFixture::class,
        DIAliasFixture::class,
        LegacyFactoriesFixture::class,
        PluginManagerFixture::class,
        MiddlewareAttributesFixture::class,
        CustomFixture::class,
        SourceFixture::class,
    ];

    public function configure() : void
    {
        $this->setName('rewrite')
             ->setDescription('Rewrite files in the repository')
             ->addArgument(
                 'repository',
                 InputArgument::REQUIRED,
                 'The repository name to rewrite'
             );
    }

    /**
     * @throws RuntimeException When given repository is already rewritten.
     */
    public function execute(InputInterface $input, OutputInterface $output) : int
    {
        $repository = new Repository(
            $input->getArgument('repository')
        );

        $composer = current($repository->files('composer.json'));
        if ($composer) {
            $json = json_decode(file_get_contents($composer), true);
            if (isset($json['require']['laminas/laminas-zendframework-bridge'])) {
                throw new RuntimeException(sprintf(
                    'Repository %s seems to be already rewritten.',
                    $repository->getName()
                ));
            }
        }

        foreach ($this->fixtures as $fixtureName) {
            /** @var AbstractFixture $fixture */
            $fixture = new $fixtureName($output);
            $fixture->process($repository);
        }

        return 0;
    }
}
