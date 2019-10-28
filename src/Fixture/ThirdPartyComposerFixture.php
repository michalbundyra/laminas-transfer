<?php

declare(strict_types=1);

namespace Laminas\Transfer\Fixture;

use Laminas\Transfer\Helper\JsonWriter;
use Laminas\Transfer\Repository;
use Laminas\Transfer\ThirdPartyRepository;

use function current;
use function file_get_contents;
use function json_decode;
use function ksort;
use function unlink;

use const SORT_NATURAL;

/**
 * Migrate a composer.json for a userland project or third-party library.
 *
 * Replaces all references to ZF/ZendFramework/Zend in composer.json.
 *
 * Deletes composer.lock file.
 *
 * Adds laminas-dependency-plugin as a dependency, ensuring Laminas packages
 * are used in place of ZF packages when installed as nested dependencies.
 */
class ThirdPartyComposerFixture extends AbstractFixture
{
    public function process(Repository $repository) : void
    {
        $composer = current($repository->files('composer.json'));
        if (! $composer) {
            $this->writeln('<error>SKIP</error> No composer.json found.');
            return;
        }

        $composerLock = current($repository->files('composer.lock'));
        if ($composerLock) {
            unlink($composerLock);
        }

        $content = file_get_contents($composer);
        $originName = json_decode($content, true)['name'] ?? null;
        $content = $repository->replace($content);

        $json = json_decode($content, true);

        // Remove the type of library, as it is default type
        if (isset($json['type']) && $json['type'] === 'library') {
            unset($json['type']);
        }

        if ($repository instanceof ThirdPartyRepository
            && $repository->installDependencyPlugin()
        ) {
            $json = $this->injectDependencyPlugin($json);
        }

        // Sort packages
        $json['config']['sort-packages'] = true;

        JsonWriter::write($composer, $json);
    }

    private function injectDependencyPlugin(array $json) : array
    {
        // Add dependency for laminas-dependency-plugin
        $json['require'] = $json['require'] ?? [];
        $json['require']['laminas/laminas-dependency-plugin'] = '^0.1.1';

        // Sort packages
        ksort($json['require'], SORT_NATURAL);

        return $json;
    }
}
