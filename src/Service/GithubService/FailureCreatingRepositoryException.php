<?php

declare(strict_types=1);

namespace Laminas\Transfer\Service\GithubService;

use RuntimeException;
use Throwable;

use function sprintf;

class FailureCreatingRepositoryException extends RuntimeException implements GithubServiceExceptionInterface
{
    public static function forPackage(string $org, string $repo, Throwable $previous) : self
    {
        return new self(sprintf(
            'Could not create package %s/%s on GitHub',
            $org,
            $repo
        ), $previous->getCode(), $previous);
    }
}
