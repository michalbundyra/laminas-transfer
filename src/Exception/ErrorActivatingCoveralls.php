<?php

declare(strict_types=1);

namespace Laminas\Transfer\Exception;

use Psr\Http\Message\ResponseInterface;
use RuntimeException;

class ErrorActivatingCoveralls extends RuntimeException implements ExceptionInterface
{
    public static function forResponse(ResponseInterface $response, string $repository) : self
    {
        return new self(sprintf(
            "An error was returned when activating coveralls for %s:\n%s",
            $repository,
            FormatResponse::serializeResponse($response)
        ), $response->getStatusCode());
    }
}
