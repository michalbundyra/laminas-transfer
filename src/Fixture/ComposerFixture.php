<?php

declare(strict_types=1);

namespace Laminas\Transfer\Fixture;

use Laminas\Transfer\Repository;
use Localheinz\Composer\Json\Normalizer\ComposerJsonNormalizer;
use Localheinz\Json\Normalizer\Json;

use function current;
use function file_get_contents;
use function file_put_contents;
use function json_decode;
use function json_encode;
use function unlink;

use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_SLASHES;
use const PHP_EOL;

/**
 * Replaces all references to ZF/ZendFramework/Zend in composer.json.
 * Normalizes the composer.json file (sorting packages alphabetically).
 * Adding "replace" section.
 * Deleting composer.lock file.
 */
class ComposerFixture extends AbstractFixture
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
        $content = $repository->replace($content);

        $json = json_decode($content, true);
        $json['replace'] = [$repository->getName() => 'self.version'];

        $normalizer = new ComposerJsonNormalizer();
        $json = Json::fromEncoded(json_encode($json));
        $normalized = $normalizer->normalize($json);

        $content = json_encode($normalized->decoded(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;

        $this->writeln($content);

        file_put_contents($composer, $content);
    }
}
