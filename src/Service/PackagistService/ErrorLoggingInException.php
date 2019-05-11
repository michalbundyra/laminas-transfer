<?php

declare(strict_types=1);

namespace Laminas\Transfer\Service\PackagistService;

use Laminas\Transfer\Exception\FormatResponse;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

use function sprintf;

class ErrorLoggingInException extends RuntimeException implements PackagistServiceExceptionInterface
{
    public static function forResponse(ResponseInterface $response) : self
    {
        return new self(sprintf(
            'An unexpected response status was returned when attempting to login to Packagist;'
                . " expected 302, received %d:\n%s",
            $response->getStatusCode(),
            FormatResponse::serializeResponse($response)
        ), $response->getStatusCode());
    }
}
