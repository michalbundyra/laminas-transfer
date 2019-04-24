<?php

declare(strict_types=1);

namespace Laminas\Transfer\Exception;

use Psr\Http\Message\ResponseInterface;
use RuntimeException;

class ErrorLoggingIn extends RuntimeException implements ExceptionInterface
{
    use FormatResponseTrait;

    public static function forResponse(ResponseInterface $response) : self
    {
        return new self(sprintf(
            'An unexpected response status was returned when attempting to login to Packagist;'
            . " expected 302, received %d:\n%s",
            $response->getStatusCode(),
            $this->serializeResponse($response)
        ), $response->getStatusCode());
    }
}
