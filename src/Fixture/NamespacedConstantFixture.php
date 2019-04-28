<?php

declare(strict_types=1);

namespace Laminas\Transfer\Fixture;

use Laminas\Transfer\Helper\JsonWriter;
use Laminas\Transfer\Repository;

use function current;
use function file_get_contents;
use function file_put_contents;
use function json_decode;
use function preg_match;
use function preg_match_all;
use function realpath;
use function str_replace;

use const PHP_EOL;

class NamespacedConstantFixture extends AbstractFixture
{
    public function process(Repository $repository) : void
    {
        $composer = current($repository->files('composer.json'));
        if (! $composer) {
            return;
        }

        $composerContent = json_decode(file_get_contents($composer), true);

        $files = [];
        foreach ($composerContent['autoload']['files'] ?? [] as $file) {
            $additionalFile = $this->rewriteConstants($repository, $file);

            if ($additionalFile) {
                $composerContent['autoload']['files'][] = $additionalFile;
                $files[] = realpath($additionalFile);
            }
        }

        if ($files) {
            JsonWriter::write($composer, $composerContent);
            $repository->addReplacedContentFiles($files);
        }
    }

    private function rewriteConstants(Repository $repository, string $file) : ?string
    {
        $content = file_get_contents($file);

        // No namespace detected
        if (! preg_match('/<\?php.+?^namespace\s+(.+?);/ms', $content, $namespace)) {
            $this->writeln('Cannot detect namespace');
            return null;
        }

        preg_match_all('/^use .+?;/ms', $content, $uses);
        $uses = $uses[0] ?? [];

        if (! preg_match_all('/^const\s+(.*?)\s*=/m', $content, $constants)) {
            $this->writeln('Cannot detect constants');
            return null;
        }

        $newFile = str_replace('.php', '.legacy.php', $file);
        $newNamespace = $repository->replace($namespace[1]);

        $newContent = $namespace[0] . PHP_EOL;
        foreach ($constants[1] as $constant) {
            $newContent .= PHP_EOL . '/**' . PHP_EOL
                . ' * @deprecated Please use ' . $newNamespace . '\\' . $constant . ' instead' . PHP_EOL
                . ' */' . PHP_EOL
                . 'const ' . $constant . ' = \\' . $newNamespace . '\\' . $constant . ';' . PHP_EOL;
        }

        file_put_contents($newFile, $newContent);

        return $newFile;
    }
}
