<?php

declare(strict_types=1);

namespace Laminas\Transfer\Service\GithubService;

use RuntimeException;
use Throwable;

use function sprintf;

class FailureSettingRepositoryTopicsException extends RuntimeException implements GithubServiceExceptionInterface
{
    public static function forRepository(string $org, string $repo, Throwable $previous) : self
    {
        return new self(sprintf(
            'Could not set topics for repository %s/%s on GitHub',
            $org,
            $repo
        ), $previous->getCode(), $previous);
    }
}
