<?php

declare(strict_types=1);

namespace Laminas\Transfer\Fixture\Custom;

use Laminas\Transfer\Fixture\AbstractFixture;
use Laminas\Transfer\Repository;

use function file_get_contents;
use function file_put_contents;
use function strtr;

/**
 * Process some test files
 */
class ZendFilter extends AbstractFixture
{
    public function process(Repository $repository) : void
    {
        $this->replaceFiles(
            $repository,
            '*/UnderscoreToCamelCaseTest.php',
            [
                'Framework\\' => 'Framework\\',
                'Framework_' => 'Framework_',
                'framework' => 'project',
                'Framework' => 'Project',
            ]
        );

        $this->replaceFiles(
            $repository,
            '*/BlockCipherTest.php',
            [
                'ZendFramework2.0' => 'Laminas__Project',
                // @phpcs:disable Generic.Files.LineLength.TooLong
                '5b68e3648f9136e5e9bfaa2242e5b668e7501b2c20e8f9e2c76638f017f62a8eWmVuZEZyYW1ld29yazIuMDpd5vWydswa0fyIo2dnF0Q=' => '972c29fe2ac804e7adab21aa15b2896215e2daf227d82f92734da074c24095abTGFtaW5hc19fUHJvamVjdGK1rPNgf9xxr8Croef2PRs=',
                'c7da11b89330f6bbbb15fcb6de574c7ec869ad7187a7d466e60f2437914d927aWmVuZEZyYW1ld29yazIuMKXsBdYXBLQx9elx0B20uxQ=' => '8b3fcdd53a5833257f27e1a35fa715c0e023da85240f22e32f9fd5ed790431a8TGFtaW5hc19fUHJvamVjdGHsk81/w1rQQXN6RpRYDqI=',
                'ca1b9df732facf9dfadc7c3fdf1ccdc211bf21f638d459f43fefc74bbc9c8e01WmVuZEZyYW1ld29yazIuMM1som/As52rdK/4g7uoYx4=' => 'f89c41712b95d1ec48efdf53f23488bae0b2d8cb28915fbf90a8f630bade3dd1TGFtaW5hc19fUHJvamVjdE53RnguL2HyRLU4a5m9RWU=',
                // @phpcs:enable
            ]
        );
    }

    protected function replaceFiles(Repository $repository, string $pattern, array $replacementPairs) : void
    {
        $files = $repository->files($pattern);
        foreach ($files as $file) {
            $content = file_get_contents($file);
            $content = strtr($content, $replacementPairs);
            $content = $repository->replace($content);
            file_put_contents($file, $content);
        }

        $repository->addReplacedContentFiles($files);
    }
}
