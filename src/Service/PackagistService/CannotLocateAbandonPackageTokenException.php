<?php

declare(strict_types=1);

namespace Laminas\Transfer\Service\PackagistService;

use Laminas\Transfer\Exception\FormatResponse;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

use function sprintf;

class CannotLocateAbandonPackageTokenException extends RuntimeException implements PackagistServiceExceptionInterface
{
    public static function forResponse(ResponseInterface $response, string $url) : self
    {
        return new self(sprintf(
            "Could not find the abandon package token for the package at url %s:\n%s",
            $url,
            FormatResponse::serializeResponse($response)
        ), $response->getStatusCode());
    }
}
