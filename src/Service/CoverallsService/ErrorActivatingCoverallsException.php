<?php

declare(strict_types=1);

namespace Laminas\Transfer\Service\CoverallsService;

use Psr\Http\Message\ResponseInterface;
use RuntimeException;

use function sprintf;

class ErrorActivatingCoverallsException extends RuntimeException implements CoverallsServiceExceptionInterface
{
    public static function forResponse(ResponseInterface $response, string $repository) : self
    {
        return new self(sprintf(
            "An error was returned when activating coveralls for %s:\n%s",
            $repository,
            Exception\FormatResponse::serializeResponse($response)
        ), $response->getStatusCode());
    }
}
