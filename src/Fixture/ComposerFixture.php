<?php

declare(strict_types=1);

namespace Laminas\Transfer\Fixture;

use Laminas\Transfer\Helper\JsonWriter;
use Laminas\Transfer\Repository;
use Localheinz\Composer\Json\Normalizer\ComposerJsonNormalizer;
use Localheinz\Json\Normalizer\Json;

use function array_unique;
use function array_unshift;
use function current;
use function file_get_contents;
use function json_decode;
use function json_encode;
use function unlink;

/**
 * Replaces all references to ZF/ZendFramework/Zend in composer.json.
 * Normalizes the composer.json file (sorting packages alphabetically).
 * Adds "replace" section into composer.json file.
 * Deletes composer.lock file.
 */
class ComposerFixture extends AbstractFixture
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
        $content = $repository->replace($content);

        $json = json_decode($content, true);
        $json['replace'] = [$repository->getName() => 'self.version'];
        if (isset($json['keywords'])) {
            // Prepend "laminas" keyword
            array_unshift($json['keywords'], 'laminas');
            // @todo: Prepend "apigility" / "expressive" keyword (depends on the project)
            $json['keywords'] = array_unique($json['keywords']);
        }
        $json['license'] = 'BSD-3-Clause';

        $normalizer = new ComposerJsonNormalizer();
        $json = Json::fromEncoded(json_encode($json));
        $normalized = $normalizer->normalize($json);

        JsonWriter::write($composer, $normalized->decoded());
    }
}
