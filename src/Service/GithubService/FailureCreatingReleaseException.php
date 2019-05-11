<?php

declare(strict_types=1);

namespace Laminas\Transfer\Service\GithubService;

use RuntimeException;
use Throwable;

use function sprintf;

class FailureCreatingReleaseException extends RuntimeException implements GithubServiceExceptionInterface
{
    public static function forPackageVersion(string $org, string $repo, string $version, Throwable $previous) : self
    {
        return new self(sprintf(
            'Could not create release %s for package %s/%s on GitHub',
            $version,
            $org,
            $repo
        ), $previous->getCode(), $previous);
    }
}
