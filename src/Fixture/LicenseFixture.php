<?php

declare(strict_types=1);

namespace Laminas\Transfer\Fixture;

use Laminas\Transfer\Repository;

use function current;
use function date;
use function file_get_contents;
use function file_put_contents;
use function preg_replace;
use function sprintf;
use function system;

/**
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

    private const LICENSE = <<<'LICENSE'
Copyright (c) %u, Laminas Foundation
All rights reserved.

Redistribution and use in source and binary forms, with or without modification,
are permitted provided that the following conditions are met:

- Redistributions of source code must retain the above copyright notice, this
  list of conditions and the following disclaimer.

- Redistributions in binary form must reproduce the above copyright notice, this
  list of conditions and the following disclaimer in the documentation and/or
  other materials provided with the distribution.

- Neither the name of Laminas Foundation nor the names of its contributors may
  be used to endorse or promote products derived from this software without
  specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
(INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON
ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
(INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

LICENSE;

    public function process(Repository $repository) : void
    {
        $license = current($repository->files('LICENSE.md'));
        if ($license) {
            file_put_contents($license, sprintf(self::LICENSE, date('Y')));
        }

        $phps = $repository->files('*.php', true);
        foreach ($phps as $php) {
            $this->replace($repository, $php);
        }

        file_put_contents(
            $repository->getPath() . '/COPYRIGHT.md',
            'Copyright (c) ' . date('Y') . ', Laminas Foundation.'
                . "\n" . 'All rights reserved. (https://getlaminas.org/)'
        );
        system('git add ' . $repository->getPath() . '/COPYRIGHT.md');

        $docheader = current($repository->files('.docheader'));
        if ($docheader) {
            $this->replace($repository, $docheader);
        }
    }

    protected function replace(Repository $repository, string $file) : void
    {
        $content = file_get_contents($file);

        $content = preg_replace(
            '/\/\*\*.+?@license.+? \*\//s',
            sprintf(self::HEADER, $repository->getNewName()),
            $content
        );

        file_put_contents($file, $content);
    }
}
