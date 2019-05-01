<?php

declare(strict_types=1);

namespace Laminas\Transfer\Exception;

use Psr\Http\Message\ResponseInterface;
use RuntimeException;

use function sprintf;

class CouldNotAbandonPackage extends RuntimeException implements ExceptionInterface
{
    public static function forResponse(ResponseInterface $response, string $package) : self
    {
        return new self(sprintf(
            'An unexpected response status was returned when attempting to abandon the package %s;'
                . " expected 302, received %d:\n%s",
            $package,
            $response->getStatusCode(),
            FormatResponse::serializeResponse($response)
        ), $response->getStatusCode());
    }
}
