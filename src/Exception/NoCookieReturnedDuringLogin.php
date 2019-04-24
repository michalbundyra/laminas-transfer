<?php

declare(strict_types=1);

namespace Laminas\Transfer\Exception;

use Psr\Http\Message\ResponseInterface;
use RuntimeException;

class NoCookieReturnedDuringLogin extends RuntimeException implements ExceptionInterface
{
    use FormatResponseTrait;

    public static function forResponse(ResponseInterface $response) : self
    {
        return new self(sprintf(
            "Did not receive a cookie in response to logging in to Packagist:\n%s",
            $this->serializeResponse($response)
        ), $response->getStatusCode());
    }
}
