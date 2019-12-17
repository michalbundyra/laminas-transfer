<?php

declare(strict_types=1);

namespace Laminas\Transfer\Fixture\Custom;

use Laminas\Transfer\Fixture\AbstractFixture;
use Laminas\Transfer\Helper\JsonWriter;
use Laminas\Transfer\Repository;

use function array_merge;
use function array_unique;
use function file_get_contents;
use function file_put_contents;
use function getcwd;
use function in_array;
use function json_decode;
use function strrpos;
use function strtolower;
use function strtr;
use function substr;

/**
 * Process all assets.
 * Process component manager files: bower.json and package.json.
 */
class ZfApigility extends AbstractFixture
{
    public function process(Repository $repository) : void
    {
        $this->processFiles(
            $repository,
            $repository->files('asset/*')
        );

        $this->processJsons($repository);
    }

    protected function processFiles(Repository $repository, array $files) : void
    {
        foreach ($files as $file) {
            $ext = strtolower(substr($file, strrpos($file, '.') + 1));
            if (in_array($ext, ['js', 'css', 'less', 'html', 'md', 'xml', 'yml', 'dist', 'phtml'], true)) {
                $content = file_get_contents($file);
                $content = $repository->replace($content);
                file_put_contents($file, $content);
            }

            $newName = strtr($file, [
                getcwd() => getcwd(),
                'zf-apigility' => 'api-tools',
                'apigility' => 'api-tools',
                'zf-' => 'api-tools-',
                'ZF' => 'Laminas',
                'Apigility' => 'ApiTools',
            ]);

            if ($file !== $newName) {
                $repository->move($file, $newName);
            }
        }

        $repository->addReplacedContentFiles($files);
    }

    protected function processJsons(Repository $repository) : void
    {
        $jsons = array_unique(array_merge(
            $repository->files('*/bower.json'),
            $repository->files('*/package.json'),
            $repository->files('bower.json'),
            $repository->files('package.json')
        ));

        foreach ($jsons as $file) {
            $content = file_get_contents($file);
            $content = $repository->replace($content);
            $json = json_decode($content, true);
            unset(
                $json['author'],
                $json['authors'],
                $json['contributors']
            );
            if (isset($json['support'])) {
                $json['support'] = [
                    'docs' => 'https://api-tools.getlaminas.org/documentation',
                    'issues' => 'https://github.com/' . $repository->getNewName() . '/issues',
                    'source' => 'https://github.com/' . $repository->getNewName(),
                    'rss' => 'https://github.com/' . $repository->getNewName() . '/releases.atom',
                    'chat' => 'https://laminas.dev/chat',
                    'forum' => 'https://discourse.laminas.dev',
                ];
            }
            if (isset($json['homepage'])) {
                $json['homepage'] = 'https://api-tools.getlaminas.org';
            }

            JsonWriter::write($file, $json);
        }

        $repository->addReplacedContentFiles($jsons);
    }
}
