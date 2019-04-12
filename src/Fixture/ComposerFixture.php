<?php

declare(strict_types=1);

namespace Laminas\Transfer\Fixture;

use Laminas\Transfer\Helper\JsonWriter;
use Laminas\Transfer\Repository;
use Localheinz\Composer\Json\Normalizer\BinNormalizer;
use Localheinz\Composer\Json\Normalizer\ConfigHashNormalizer;
use Localheinz\Composer\Json\Normalizer\PackageHashNormalizer;
use Localheinz\Composer\Json\Normalizer\VersionConstraintNormalizer;
use Localheinz\Json\Normalizer\ChainNormalizer;
use Localheinz\Json\Normalizer\Json;
use Localheinz\Json\Normalizer\NormalizerInterface;

use function array_unique;
use function array_unshift;
use function array_values;
use function current;
use function explode;
use function file_get_contents;
use function json_decode;
use function json_encode;
use function unlink;

/**
 * Replaces all references to ZF/ZendFramework/Zend in composer.json.
 * Normalizes the composer.json file (sorting packages alphabetically).
 * Adds "replace" section into composer.json file.
 * Deletes composer.lock file.
 * Adds "laminas/laminas-zendframework-bridge" dependency to require section.
 * Updates license to BSD-3-Clause.
 * Prepends "laminas" and "expressive"/"apigility" keywords.
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

        [$org, $name] = explode('/', $repository->getNewName(), 2);

        $json = json_decode($content, true);

        // Remove the type if library, as it is default type
        if (isset($json['type']) && $json['type'] === 'library') {
            unset($json['type']);
        }

        // Remove the authors, version, readme, time
        unset($json['authors'], $json['version'], $json['readme'], $json['time']);

        $json['homepage'] = 'https://' . $org . '.dev';

        // Sort packages
        $json['config']['sort-packages'] = true;

        // Update all support links
        $json['support'] = [
            'docs' => 'https://docs.' . $org . '.dev/' . $name . '/',
            'issues' => 'https://github.com/' . $repository->getNewName() . '/issues',
            'source' => 'https://github.com/' . $repository->getNewName(),
            'rss' => 'https://github.com/' . $repository->getNewName() . '/releases.atom',
            'chat' => 'https://laminas.dev/slack',
            'forum' => 'https://discourse.laminas.dev',
        ];

        if ($org === 'apigility') {
            unset($json['support']['docs']);
        }

        $json['require']['laminas/laminas-zendframework-bridge'] = '^0.3 || ^1.0';
        $json['require-dev']['roave/security-advisories'] = 'dev-master';
        $json['replace'] = [$repository->getName() => 'self.version'];
        if (isset($json['keywords'])) {
            array_unshift($json['keywords'], 'laminas', $org);
            $json['keywords'] = array_values(array_unique($json['keywords']));
        }
        $json['license'] = 'BSD-3-Clause';

        $json = Json::fromEncoded(json_encode($json));
        $normalized = $this->getNormalizer()->normalize($json);

        JsonWriter::write($composer, $normalized->decoded());
    }

    private function getNormalizer() : NormalizerInterface
    {
        return new ChainNormalizer(
            new BinNormalizer(),
            new ConfigHashNormalizer(),
            new PackageHashNormalizer(),
            new VersionConstraintNormalizer()
        );
    }
}
