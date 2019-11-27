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

use function current;
use function explode;
use function file_get_contents;
use function in_array;
use function json_decode;
use function json_encode;

class LocalFixture extends AbstractFixture
{
    public function process(Repository $repository) : void
    {
        $composer = current($repository->files('composer.json'));
        if (! $composer) {
            return;
        }

        $content = file_get_contents($composer);
        $json = json_decode($content, true);
        $json['version'] = '9.9.9';
        foreach (['require', 'require-dev'] as $section) {
            foreach ($json[$section] ?? [] as $repo => $constraint) {
                $this->addLocalRepository($section, $repo, $json);
            }
        }

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

    private function addLocalRepository(string $section, string $repo, array &$json) : void
    {
        [$org, $name] = explode('/', $repo);

        if ($name === 'laminas-zendframework-bridge'
            || ! in_array($org, ['laminas', 'mezzio', 'laminas-api-tools'], true)
        ) {
            return;
        }

        $json[$section][$repo] = '9.9.9';

        $json['repositories'][] = [
            'type' => 'path',
            'url' => '../' . $name,
        ];
    }
}
