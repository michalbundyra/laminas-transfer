<?php

declare(strict_types=1);

namespace Laminas\Transfer\Service\GithubService;

use RuntimeException;
use Throwable;

use function sprintf;

class FailureSettingRepositoryIssueLabelsException extends RuntimeException implements GithubServiceExceptionInterface
{
    public static function forPackage(string $org, string $repo, Throwable $previous) : self
    {
        return new self(sprintf(
            'Could not create one or more issue labels for package %s/%s on GitHub',
            $org,
            $repo
        ), $previous->getCode(), $previous);
    }
}
