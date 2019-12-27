<?php

declare(strict_types=1);

namespace Laminas\Transfer\Fixture\Custom;

use Laminas\Transfer\Fixture\AbstractFixture;
use Laminas\Transfer\Repository;

use function file_get_contents;
use function file_put_contents;
use function str_replace;
use function substr;

/**
 * Rewrite tests expectations
 */
class ZendCode extends AbstractFixture
{
    private const SEARCH = <<<'HEADER'
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source
 * repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc.
 * (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */
HEADER;

    private const REPLACE = <<<'HEADER'
/**
 * @see       https://github.com/laminas/laminas-code for the canonical source
 * repository
 * @copyright https://github.com/laminas/laminas-code/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-code/blob/master/LICENSE.md New
 * BSD License
 */
HEADER;

    public function process(Repository $repository) : void
    {
        $files = $repository->files('*/FileGeneratorTest.php');
        foreach ($files as $file) {
            $content = file_get_contents($file);
            $content = str_replace(self::SEARCH, self::REPLACE, $content);
            $content = $repository->replace($content);
            $content = substr($content, 0, 5)
                . str_replace(
                    '<?php' . "\n\n" . '/**',
                    '<?php' . "\n" . '/**',
                    substr($content, 5)
                );
            file_put_contents($file, $content);
        }

        $repository->addReplacedContentFiles($files);
    }
}
