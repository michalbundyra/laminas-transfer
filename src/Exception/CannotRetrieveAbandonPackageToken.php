<?php

declare(strict_types=1);

namespace Laminas\Transfer\Exception;

use Psr\Http\Message\ResponseInterface;
use RuntimeException;

class CannotRetrieveAbandonPackageToken extends RuntimeException implements ExceptionInterface
{
    use FormatResponseTrait;

    public static function forResponse(ResponseInterface $response, string $url) : self
    {
        return new self(sprintf(
            'An unexpected response status was returned when attempting to request the'
            . " abandon package page for url %s; expected 200, received %d:\n%s",
            $url,
            $response->getStatusCode(),
            $this->serializeResponse($response)
        ), $response->getStatusCode());
    }
}
