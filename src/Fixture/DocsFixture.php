<?php

declare(strict_types=1);

namespace Laminas\Transfer\Fixture;

use Laminas\Transfer\Repository;

use function array_merge;
use function basename;
use function current;
use function date;
use function dirname;
use function file_get_contents;
use function file_put_contents;
use function preg_replace;
use function str_replace;
use function strpos;
use function strtr;
use function system;

/**
 * Updates documentation files in doc/ or docs/ directories (*.html, *.md)
 * Renames files with "zend-"/"zf-" names
 * Updates README.md if present
 * Updates mkdocs.yml if present
 * Updates CODE_OF_CONDUCT.md/CONDUCT.md
 */
class DocsFixture extends AbstractFixture
{
    private const CODE_OF_CONDUCT = <<<'CONDUCT'
# Contributor Code of Conduct

This project adheres to [The Code Manifesto](http://codemanifesto.com)
as its guidelines for contributor interactions.

## The Code Manifesto

We want to work in an ecosystem that empowers developers to reach their
potential — one that encourages growth and effective collaboration. A space
that is safe for all.

A space such as this benefits everyone that participates in it. It encourages
new developers to enter our field. It is through discussion and collaboration
that we grow, and through growth that we improve.

In the effort to create such a place, we hold to these values:

1. **Discrimination limits us.** This includes discrimination on the basis of
   race, gender, sexual orientation, gender identity, age, nationality,
   technology and any other arbitrary exclusion of a group of people.
2. **Boundaries honor us.** Your comfort levels are not everyone’s comfort
   levels. Remember that, and if brought to your attention, heed it.
3. **We are our biggest assets.** None of us were born masters of our trade.
   Each of us has been helped along the way. Return that favor, when and where
   you can.
4. **We are resources for the future.** As an extension of #3, share what you
   know. Make yourself a resource to help those that come after you.
5. **Respect defines us.** Treat others as you wish to be treated. Make your
   discussions, criticisms and debates from a position of respectfulness. Ask
   yourself, is it true? Is it necessary? Is it constructive? Anything less is
   unacceptable.
6. **Reactions require grace.** Angry responses are valid, but abusive language
   and vindictive actions are toxic. When something happens that offends you,
   handle it assertively, but be respectful. Escalate reasonably, and try to
   allow the offender an opportunity to explain themselves, and possibly
   correct the issue.
7. **Opinions are just that: opinions.** Each and every one of us, due to our
   background and upbringing, have varying opinions. That is perfectly
   acceptable. Remember this: if you respect your own opinions, you should
   respect the opinions of others.
8. **To err is human.** You might not intend it, but mistakes do happen and
   contribute to build experience. Tolerate honest mistakes, and don't
   hesitate to apologize if you make one yourself.

CONDUCT;

    public function process(Repository $repository) : void
    {
        $docs = array_merge(
            $repository->files('doc/*'),
            $repository->files('docs/*')
        );

        foreach ($docs as $doc) {
            $this->replace($repository, $doc);

            $dirname = dirname($doc);
            $filename = basename($doc);
            $newName = $dirname . '/' . strtr($filename, [
                'zend-expressive' => 'expressive',
                'zend-' => 'laminas-',
                'zf-' => 'laminas-',
            ]);

            if (strpos($doc, 'router/zf2.md') !== false) {
                $newName = str_replace('zf2.md', 'laminas-router.md', $doc);
            }

            if ($newName !== $doc) {
                system('git mv ' . $doc . ' ' . $newName);
            }
        }

        $readme = current($repository->files('README.md'));
        if ($readme) {
            $this->replace($repository, $readme);
        }

        $mkdocs = current($repository->files('mkdocs.yml'));
        if ($mkdocs) {
            $content = file_get_contents($mkdocs);
            $content = $repository->replace($content);
            $content = preg_replace(
                '/^copyright: .*?$/m',
                'copyright: Copyright (c) ' . date('Y') . ' <a href="https://getlaminas.org">Laminas Foundation</a>',
                $content
            );
            file_put_contents($mkdocs, $content);
        }

        $mkdocsTheme = current($repository->files('.zf-mkdoc-theme-landing'));
        if ($mkdocsTheme) {
            $newName = str_replace('zf-', 'laminas-', $mkdocsTheme);
            system('git mv ' . $mkdocsTheme . ' ' . $newName);
        }

        $conducts = array_merge(
            $repository->files('docs/CODE_OF_CONDUCT.md'),
            $repository->files('doc/CODE_OF_CONDUCT.md'),
            $repository->files('CODE_OF_CONDUCT.md'),
            $repository->files('docs/CONDUCT.md'),
            $repository->files('doc/CONDUCT.md'),
            $repository->files('CONDUCT.md')
        );

        foreach ($conducts as $conduct) {
            file_put_contents($conduct, self::CODE_OF_CONDUCT);
        }
    }

    private function replace(Repository $repository, string $file) : void
    {
        $content = file_get_contents($file);
        $content = $repository->replace($content);
        file_put_contents($file, $content);
    }
}
