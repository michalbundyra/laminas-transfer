<?php

declare(strict_types=1);

namespace Laminas\Transfer\Command;

use DirectoryIterator;
use Generator;
use Laminas\Transfer\Helper\JsonWriter;
use Laminas\Transfer\Repository;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function array_pop;
use function chdir;
use function current;
use function dirname;
use function explode;
use function file_get_contents;
use function getcwd;
use function implode;
use function is_dir;
use function is_writable;
use function json_decode;
use function mkdir;
use function realpath;
use function rename;
use function sprintf;
use function str_replace;
use function system;

use const DIRECTORY_SEPARATOR;

class VendorCommand extends Command
{
    protected function configure() : void
    {
        $this->setName('vendor')
             ->setDescription('Rewrite vendor of given project')
             ->addArgument(
                 'path',
                 InputArgument::REQUIRED,
                 'The path to the project to rewrite'
             );
    }

    /**
     * @throws RuntimeException When provided directory is invalid.
     */
    protected function execute(InputInterface $input, OutputInterface $output) : void
    {
        $path = realpath(
            getcwd()
            . DIRECTORY_SEPARATOR . $input->getArgument('path')
            . DIRECTORY_SEPARATOR . 'vendor'
        );

        if (! is_dir($path) || ! is_writable($path)) {
            throw new RuntimeException(sprintf(
                'vendor directory does not exist or is not writable in given path %s',
                $input->getArgument('path')
            ));
        }

        $currentDir = getcwd();
        chdir(dirname($path));

        $repository = new Repository('webimpress/laminas-transfer');
        $installed = current($repository->files('vendor/composer/installed.json'));

        if (! $installed) {
            throw new RuntimeException(
                'Please make sure that you have installed dependencies in the project, run: composer install'
            );
        }

        $content = json_decode(file_get_contents($installed), true);

        chdir($currentDir);

        foreach ($this->iterate($path) as $dir) {
            $output->writeln($dir);
            $name = $this->rewrite($dir);

            foreach ($content as $i => $library) {
                if ($library['name'] === $name) {
                    $content[$i] = json_decode(
                        file_get_contents($repository->replace($dir) . '/composer.json'),
                        true
                    ) + ['version' => $library['version']];
                }
            }
        }

        chdir(dirname($path));

        system('git clone https://github.com/laminas/laminas-zendframework-bridge vendor/laminas/laminas-zendframework-bridge');
        $composer = json_decode(file_get_contents('vendor/laminas/laminas-zendframework-bridge/composer.json'), true);
        $composer['version'] = '1.0.0';
        $content[] = $composer;
        JsonWriter::write($installed, $content);

        system('composer dump-autoload');
    }

    private function iterate(string $path) : Generator
    {
        foreach (['zfcampus', 'zendframework'] as $org) {
            if (is_dir($path . DIRECTORY_SEPARATOR . $org)) {
                $di = new DirectoryIterator($path . DIRECTORY_SEPARATOR . $org);

                foreach ($di as $file) {
                    if ($file->isDir() && ! $file->isDot()) {
                        yield $file->getPathname();
                    }
                }
            }
        }
    }

    private function rewrite(string $path) : string
    {
        $exp = explode(DIRECTORY_SEPARATOR, $path);
        $name = array_pop($exp);
        $org = array_pop($exp);

        $currentDir = getcwd();
        chdir($path);
        system(__DIR__ . '/../../bin/console rewrite ' . $org . '/' . $name);
        chdir($currentDir);

        $repository = new Repository($org . '/' . $name);

        $newName = $repository->getNewName();
        $exp[] = str_replace('/', DIRECTORY_SEPARATOR, $newName);
        $finalDir = implode(DIRECTORY_SEPARATOR, $exp);
        $dirName = dirname($finalDir);

        if (! is_dir($dirName)) {
            mkdir($dirName, 0777, true);
        }

        rename($path, $finalDir);

        return $org . '/' . $name;
    }
}
