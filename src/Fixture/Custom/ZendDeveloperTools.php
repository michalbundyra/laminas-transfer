<?php

declare(strict_types=1);

namespace Laminas\Transfer\Fixture\Custom;

use Laminas\Transfer\Fixture\AbstractFixture;
use Laminas\Transfer\Repository;

use function array_merge;
use function basename;
use function current;
use function dirname;
use function file_get_contents;
use function file_put_contents;
use function preg_match_all;
use function preg_replace;
use function sprintf;
use function str_replace;
use function strtr;

/**
 * Renames view/zend-developer-tools to view/laminas-developer-tools
 * Rewrites JS, CSS, PH* files with additional rules
 * Renames zenddevelopertools file to laminas-developer-tools
 * Renames zendframework file to laminas
 * Removes invalid configuration option from phpunit.xml.dist
 * Replaces ZF/ZF2 logo with Laminas
 */
class ZendDeveloperTools extends AbstractFixture
{
    public function process(Repository $repository) : void
    {
        $legacyPath = $repository->getPath() . '/view/zend-developer-tools';
        $newPath = $repository->getPath() . '/view/' . $repository->replace('zend-developer-tools');
        $repository->move($legacyPath, $newPath);

        $files = array_merge(
            $repository->files('*zenddevelopertools*'),
            $repository->files('*zendframework*')
        );
        foreach ($files as $file) {
            $basename = basename($file);
            $newFile = dirname($file) . '/' . $repository->replace($basename);
            $repository->move($file, $newFile);
        }

        $phpunitConfig = current($repository->files('phpunit.xml.dist'));
        if ($phpunitConfig) {
            $content = file_get_contents($phpunitConfig);
            $content = preg_replace('/^\s+syntaxCheck="true"$\n?/m', '', $content);
            file_put_contents($phpunitConfig, $content);
        }

        $files = array_merge(
            $repository->files('*.js'),
            $repository->files('*.css'),
            $repository->files('*.ph*')
        );

        foreach ($files as $file) {
            $content = file_get_contents($file);

            // Use Placeholders for Images, so we skip base64 image content
            $images = [];

            if (! $repository->hasReplacedContent($file)) {
                // Find all base64 images and replace with placeholders
                if (preg_match_all('/src="data:image.*?"/', $content, $matches)) {
                    foreach ($matches[0] as $i => $str) {
                        $placeholder = sprintf('IMAGE_PLACEHOLDER_%07d', $i);
                        $content = str_replace($str, $placeholder, $content);

                        $images[$placeholder] = $str;
                    }
                }

                $content = $repository->replace($content);
            }
            // @phpcs:disable Generic.Files.LineLength.TooLong
            $content = strtr($content, $images + [
                'zdf-' => 'laminas-',
                'zdt-' => 'laminas-',
                'ZDT_Laminas_' => 'Laminas_Developer_Tool_',
                'http://modules.laminas.dev/' => 'https://packagist.org/?tags=module~zf2~zendframework~zend%20framework~zend%20framework%202~zf3~zf~zend~laminas',
            ]);

            $newLogo = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAEgAAAAeCAYAAACPOlitAAAABHNCSVQICAgIfAhkiAAAAAlwSFlzAAAGQAAABkABchkaRQAAABl0RVh0U29mdHdhcmUAd3d3Lmlua3NjYXBlLm9yZ5vuPBoAAAQ/SURBVGiB7ZhbqFVVFIa/oXk5CpZ5iB4UtVRSK81Cu2h6cmuRlwgJDDTp9lDgkwgFUQ8FRYGFBSEY6ItISUYPRZiamoZohgWKBWZKEWFGhJdE/XpYS5su1zl7n80+bpHzw36Y//7nnP8ca6wxx97QjW50oxtXCNQ+ap9m+wBQH1I3qS3N9HFNYfxWzj3fBC9FnAZOAOeaaaIYoH4lXFMQEZuATc320aOeSWpvdah6i9qrinZCLa+JOlC9NRn3UG8s7pt8d6d6m1r6QNVe6hB1TEdlQ21V71BHqB3HQ12prqqiWa0e93+cUl9Vo0Tbop5QZ3W4caZ9Td2RjNvyuZFwG9SX1R+S/fer4wtrLVP/TjRn1HfTYKr91HXq2UR3RJ2brlVPBn0BzAEGA0OBl4AXgUUl2slAH2BnYqxHO090JrA9GbfknxQD8v1WAP2BEcBRYH0hS7cBj+X+BpPV1GeAJYnmdWAiMAW4Drg5546VH5vaMqideWvVD0v4N9VdBe5pdXeBG5g/5QcT7uH8qaYZtFNdV5g7Mte1VfH4jro1GW9T11Q7W701qCV//0flNegQcH2JtAJsKHCzgR8L3HTgLPB1Ddt/mw4i4ifgH+Dagsfe6lh1dJ6xhwse1wDz1VXq7e1t1ukAqUuB34FdwD7gN7IDFnWtwDjgy4TrCUxNuRwVYHtEHK/BwulqnPos8CvwHbAX+AOYl2oi4v2cmwjsNeu5phUX7lSA1AVkNeBxsvrQl+z9HlUirwD/At8k3ERgIJde32WZVhfUmcByYHHury8wn6xeXYSIWA+MBdqAk8BmdXGq6WwGzQU+jYjPIsKIOBMRHwGflGgrwNaIOFngDkTEoeRAw8gKZDGr6sUcYFtErI2Ic/nnc2B1mTg/x1cRMQtYCSxNv+9sgI4C49W+5wl1CPBAiXY6lx56Rgk3E/gL2NNJLx15HK1eqEnqIOCiVkN9Uh1XmNsfOJUSxSbrFFnh2s2lWAO8ATwK7FO3kF2P04BfCpuPAoZxcf3pD0wClhXWrQAbI+JsgTf/VOOK/HvAAmC/uoHs18F04CDZ63Yes4EP1J1kl8YYYALwRLpwMUDLgSMlBgB2R8RhdTRZzzMyX3gJMAgYnmgrZE/y+4SbBvQEtlw4Vda5tpHVtSJ2AAsjIg3IK8CBEu1zZL0PEfFn3jguJKsvB/N5AvecnxAR89QpwCPADWT93aKI2NfO+RsH9eNij5F3ytsL3F15D3NTl5u6UqD2VI+pTxX4VnV4gXtB/fnyOmwy1LvzrBhWg3ajuuIy2KobdXXSVTCDwlVehvwmvJfGXe9dgq4IUIXaDn0/0BvY3AUeGoaGBii5ymsJUAXYExFHG+mh0Wh0Bk0lax22VBNS3jRe3VDfNvnTqwNda/5HVVkHfkWh0Rk0mdqyYjJZ1141mFcV1En5755qugHqfZfDUze60Vz8B9ILnHkT27skAAAAAElFTkSuQmCC';
            $content = strtr($content, [
                'data:image/jpeg;base64,iVBORw0KGgoAAAANSUhEUgAAAB8AAAAPCAYAAAAceBSiAAAABmJLR0QA/wD/AP+gvaeTAAAACXBIWXMAAC4jAAAuIwF4pT92AAAAB3RJTUUH3wkFCwsuJgWt/AAAAeVJREFUOMud1E+ITmEUx/HPOx6ZIbMkK8nCwtiMaYjsdUtKIiuNotvYCCWbsaDMwsLyNqWUjSKUXMpKspgUpig1q0HjT0JNajIPY/O8dXt739d9nbqL53TO8z3Pub9zGpCXYRQ5+rCsuy1jEI9xA6sxig0INfKhgRchHTbhCPrVsxlMYj+KVEwvtoTDfemwosfkq/iOsf8Aw9cii3ebL3+Oc6kd1bYt4Ch2YWXynS+yeD0vw4nU7l5sGe9TlwUosjiL2dbIvAx7sbECvpM+eIi3NXXSwG+8w3yRxaWms63lZRjANLYl1yzGiiw+a4nbgoNJN39agBEviyxOtWOELtXex1DlfLYNeBxXMNDhjgX86ATo6/DqS+l/NjtzEo9aYkZwqAsY5nChNjwvwx4cx9rkuoUHRRZ/tYTu/ofgZnCsTV77tudlGEywdcn1E5NFFufa5N5OmpDEVBXW5yKLH+psmir8aRqrZkd2Flmc7nZBXoZhnMJwcyKKLE7UmbtQueQiRirgRVzLy7BYKbKRciaKLN7Ly3AGlyuj+AXf6g59SODNON2yXvuxtUPemrwMQzhQAcNr3KwNz8uwHm+wqodNFbED2yu+Jxgvsvipl5cH7Ou2cNpMyKskrJm03eaLLH7sdcH/BbxPiBhQm2E3AAAAAElFTkSuQmCC'
                => $newLogo,
                'data:image/jpeg;base64,iVBORw0KGgoAAAANSUhEUgAAAB8AAAAPCAYAAAAceBSiAAAAAXNSR0IArs4c6QAAAAZiS0dEAH8A9wAAMkqpNAAAAAlwSFlzAAAuIwAALiMBeKU/dgAAAAd0SU1FB9wCCwIWGuaXOlYAAAJiSURBVDjLldS/i1xVFAfwz8leJxF/FdFUNjayhYpCkBATxEIwb0D/ADFpIjMgCDGQQtBCIWVAY/EmIGKbRpt9q6CFplIL8UfvL2SLqBBRUHzDsdi3O7Ozb7KbA493udz7Pff7Pd9zYtyUkumrCI/YfxzARvJgcDeeT54Mbs/cP0iBCNEB7i/SNMNxPIBPcCRu4eURMwaS2DNd908Ir0yq9sfgTRxZBN7jyw7xh4KpdA73LGaL8Lf0MoZia8tnddW+PVorgWdvQa0tipFsYHhTxqO18miEr3N29Y9J1R6GcVMCr+PfbfhZgl1Kd/9ppm8nw/ZjbiL3aK0U4TtptZMqUNVVu754drxWHhKOZjoYsYvwivTBZNhu9BpuSVzGagcWeK8v8agpDU7NG6nHYNdsSt0rxyLr0xHen6vUF3XVHtvFuClv4LUdFd0dv9ZVe/+4KSdxEY/hF5wvPYnvjXAps2OS/slwtudcZHphrlX7uyM9060v4Wi3XsWV0iPFhzgcIaXo2ur7Jb16Q/qyx9it9DMuT4bbd1/FIfyEb7ASC2zeifBSpuzYvFtX7dmlpmzKwKZC/9VVm/NmjTCd35sr1Sk0OBdzQI8H13BbpojwZ2ek6zmTcUud63XV3uic/pRwAceSg8Gh5L5J1f7ek/g41nG1rtoXS5d4Jfg0GUSHntwhNRELTtp8yQVcGTflOVyVBjk79NuSxA/jI3yOEZRuUq3jztgebGBF7Jx6He2MNOgecl4YzE0/OLOkSm/hLgwxHTfFgeB08PQe7bLTxrHpdpzoBn5E+gtn6qptllx9YnHjf9NI2tXkjbmCAAAAAElFTkSuQmCC'
                => $newLogo,
            ]);
            // @phpcs:enable

            file_put_contents($file, $content);
        }

        $repository->addReplacedContentFiles($files);
    }
}
