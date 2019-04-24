<?php

declare(strict_types=1);

namespace Laminas\Transfer\Exception;

use Psr\Http\Message\ResponseInterface;
use RuntimeException;

class CouldNotAbandonPackage extends RuntimeException implements ExceptionInterface
{
    use FormatResponseTrait;

    public static function forResponse(ResponseInterface $response, string $package) : self
    {
        return new self(sprintf(
            'An unexpected response status was returned when attempting to abandon the package %s;'
            . " expected 302, received %d:\n%s",
            $package,
            $response->getStatusCode(),
            $this->serializeResponse($response)
        ), $response->getStatusCode());
    }
}
