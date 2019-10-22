<?php

declare(strict_types=1);

namespace Laminas\Transfer\Fixture;

use Laminas\Transfer\Helper\JsonWriter;
use Laminas\Transfer\Repository;

use function current;
use function file_get_contents;
use function json_decode;
use function unlink;

/**
 * Replaces all references to ZF/ZendFramework/Zend in composer.json.
 * Deletes composer.lock file.
 */
class ThirdPartyComposerFixture extends AbstractFixture
{
    public function process(Repository $repository) : void
    {
        $composerLock = current($repository->files('composer.lock'));
        if ($composerLock) {
            unlink($composerLock);
        }

        $composer = current($repository->files('composer.json'));
        if (! $composer) {
            $this->writeln('<error>SKIP</error> No composer.json found.');
            return;
        }

        $content = file_get_contents($composer);
        $originName = json_decode($content, true)['name'] ?? null;
        $content = $repository->replace($content);

        $json = json_decode($content, true);

        // Remove the type if library, as it is default type
        if (isset($json['type']) && $json['type'] === 'library') {
            unset($json['type']);
        }

        // Sort packages
        $json['config']['sort-packages'] = true;

        JsonWriter::write($composer, $json);
    }
}
