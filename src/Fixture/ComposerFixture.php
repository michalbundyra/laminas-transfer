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

use function array_search;
use function array_unique;
use function array_unshift;
use function array_values;
use function current;
use function explode;
use function file_get_contents;
use function json_decode;
use function json_encode;
use function uksort;
use function unlink;

/**
 * Replaces all references to ZF/ZendFramework/Zend in composer.json.
 * Normalizes the composer.json file (sorting packages alphabetically).
 * Adds "replace" section into composer.json file.
 * Deletes composer.lock file.
 * Adds "laminas/laminas-zendframework-bridge" dependency to require section.
 * Updates license to BSD-3-Clause.
 * Prepends "laminas" and "expressive"/"apigility" keywords.
 * Sorts sections as defined in constant.
 * Removes redundant sections.
 */
class ComposerFixture extends AbstractFixture
{
    private const SECTION_ORDER = [
        'name',
        'description',
        'type',
        'license',
        'keywords',
        'homepage',
        'support',
        'config',
        'extra',
        'require',
        'require-dev',
        'provide',
        'conflict',
        'suggest',
        'autoload',
        'autoload-dev',
        'bin',
        'scripts',
        'replace',
    ];

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

        [$org, $name] = explode('/', $repository->getNewName(), 2);

        $json = json_decode($content, true);

        // Remove the type if library, as it is default type
        if (isset($json['type']) && $json['type'] === 'library') {
            unset($json['type']);
        }

        // Remove some redundant sections
        unset(
            $json['authors'],
            $json['version'],
            $json['readme'],
            $json['time'],
            $json['minimum-stability'],
            $json['prefer-stable']
        );

        $json['homepage'] = 'https://' . $org . '.dev';

        // Sort packages
        $json['config']['sort-packages'] = true;

        // Update all support links
        $json['support'] = [
            'docs' => 'https://docs.' . $org . '.dev/' . $name . '/',
            'issues' => 'https://github.com/' . $repository->getNewName() . '/issues',
            'source' => 'https://github.com/' . $repository->getNewName(),
            'rss' => 'https://github.com/' . $repository->getNewName() . '/releases.atom',
            'chat' => 'https://laminas.dev/chat',
            'forum' => 'https://discourse.laminas.dev',
        ];

        if ($org === 'apigility') {
            $json['support']['docs'] = 'https://apigility.org/documentation';
        }

        $json['require']['laminas/laminas-zendframework-bridge'] = '^0.3 || ^1.0';
        $json['require-dev']['roave/security-advisories'] = 'dev-master';
        $json['replace'] = [$originName ?? $repository->getName() => 'self.version'];
        if (isset($json['keywords'])) {
            array_unshift($json['keywords'], 'laminas', $org);
            $json['keywords'] = array_values(array_unique($json['keywords']));
        }
        $json['license'] = 'BSD-3-Clause';

        // Sorting sections
        uksort($json, static function (string $a, string $b) {
            $ia = array_search($a, self::SECTION_ORDER, true);
            $ib = array_search($b, self::SECTION_ORDER, true);

            if ($ia === $ib) {
                return 0;
            }

            if ($ia === false) {
                return 1;
            }

            if ($ib === false) {
                return -1;
            }

            if ($ia < $ib) {
                return -1;
            }

            return 1;
        });

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
