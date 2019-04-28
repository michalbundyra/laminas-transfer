<?php

declare(strict_types=1);

namespace Laminas\Transfer\Fixture;

use Laminas\Transfer\Helper\JsonWriter;
use Laminas\Transfer\Repository;

use function current;
use function file_get_contents;
use function file_put_contents;
use function implode;
use function json_decode;
use function preg_match;
use function preg_match_all;
use function realpath;
use function sprintf;
use function str_replace;

use const PHP_EOL;

class FunctionAliasFixture extends AbstractFixture
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
            $additionalFile = $this->rewriteFunctions($repository, $file);

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

    private function rewriteFunctions(Repository $repository, string $file) : ?string
    {
        $content = file_get_contents($file);

        // No namespace detected
        if (! preg_match('/<\?php.+?^namespace\s+(.+?);/ms', $content, $namespace)) {
            $this->writeln('Cannot detect namespace');
            return null;
        }

        preg_match_all('/^use .+?;/ms', $content, $uses);
        $uses = $uses[0] ?? [];

        if (! preg_match_all('/^function (.+?)\(.+?{/ms', $content, $functions)) {
            $this->writeln('Cannot detect functions');
            return null;
        }

        $newNamespace = $repository->replace($namespace[1]);
        $newFile = str_replace('.php', '.legacy.php', $file);
        $newContent = $namespace[0] . PHP_EOL;

        if ($uses) {
            $newContent .= PHP_EOL . $repository->replace(implode(PHP_EOL, $uses)) . PHP_EOL;
        }

        foreach ($functions[1] as $functionName) {
            $newContent .= PHP_EOL . 'use function '
                . $newNamespace . '\\'
                . $functionName . ' as laminas_' . $functionName . ';';
        }
        $newContent .= PHP_EOL;

        foreach ($functions[0] as $i => $function) {
            $newContent .= PHP_EOL . '/**' . PHP_EOL
                . sprintf(' * @deprecated Use %s instead', $newNamespace . '\\' . $functions[1][$i])
                . PHP_EOL . ' */'
                . PHP_EOL . $function . PHP_EOL
                . '    laminas_' . $functions[1][$i] . '(...func_get_args());' . PHP_EOL
                . '}' . PHP_EOL;
        }

        file_put_contents($newFile, $newContent);

        return $newFile;
    }
}
