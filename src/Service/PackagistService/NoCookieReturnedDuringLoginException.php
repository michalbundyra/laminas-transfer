<?php

declare(strict_types=1);

namespace Laminas\Transfer\Service\PackagistService;

use Laminas\Transfer\Exception\FormatResponse;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

use function sprintf;

class NoCookieReturnedDuringLoginException extends RuntimeException implements PackagistServiceExceptionInterface
{
    public static function forResponse(ResponseInterface $response) : self
    {
        return new self(sprintf(
            "Did not receive a cookie in response to logging in to Packagist:\n%s",
            FormatResponse::serializeResponse($response)
        ), $response->getStatusCode());
    }
}
