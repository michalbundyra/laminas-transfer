<?php

declare(strict_types=1);

namespace Laminas\Transfer\Fixture;

use Laminas\Transfer\Repository;

use function array_merge;
use function current;
use function file_get_contents;
use function file_put_contents;
use function preg_replace;
use function sprintf;
use function str_replace;

/**
 * Renames LICENSE.txt to LICENSE.md
 * Updates LICENSE.md
 * Updates license headers in all *.php files
 * Adds COPYRIGHT.md file
 * Updates .docheader if exist
 */
class LicenseFixture extends AbstractFixture
{
    private const HEADER = <<<'EOS'
/**
 * @see       https://github.com/%1$s for the canonical source repository
 * @copyright https://github.com/%1$s/blob/master/COPYRIGHT.md
 * @license   https://github.com/%1$s/blob/master/LICENSE.md New BSD License
 */
EOS;

    public function process(Repository $repository) : void
    {
        $licenseTxt = current($repository->files('LICENSE.txt'));
        if ($licenseTxt) {
            $repository->move($licenseTxt, str_replace('.txt', '.md', $licenseTxt));
        }

        $license = current($repository->files('LICENSE.md'));
        if ($license) {
            file_put_contents($license, $repository->getTemplateText($repository::T_LICENSE));
        }

        $phps = array_merge(
            $repository->files('*.php'),
            $repository->files('*.php.*'),
            $repository->files('bin/*')
        );
        foreach ($phps as $php) {
            $this->replace($repository, $php);
        }

        file_put_contents(
            $repository->getPath() . '/COPYRIGHT.md',
            $repository->getTemplateText($repository::T_COPYRIGHT)
        );
        $repository->add($repository->getPath() . '/COPYRIGHT.md');

        $docheader = current($repository->files('.docheader'));
        if ($docheader) {
            $this->replace($repository, $docheader);
        }
    }

    private function replace(Repository $repository, string $file) : void
    {
        $content = file_get_contents($file);

        $content = preg_replace(
            '/\/\*\*.+?@license.+?^\s*\*\//sm',
            sprintf(self::HEADER, $repository->getNewName()),
            $content,
            1
        );

        $content = str_replace("<?php\n/**", "<?php\n\n/**", $content);

        file_put_contents($file, $content);
    }
}
