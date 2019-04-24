<?php

declare(strict_types=1);

namespace Laminas\Transfer\Exception;

use Psr\Http\Message\ResponseInterface;
use RuntimeException;

class CannotLocateAbandonPackageToken extends RuntimeException implements ExceptionInterface
{
    use FormatResponseTrait;

    public static function forResponse(ResponseInterface $response, string $url) : self
    {
        return new self(sprintf(
            "Could not find the abandon package token for the package at url %s:\n%s",
            $url,
            $this->serializeResponse($response)
        ), $response->getStatusCode());
    }
}
