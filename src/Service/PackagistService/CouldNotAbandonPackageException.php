<?php

declare(strict_types=1);

namespace Laminas\Transfer\Service\PackagistService;

use Laminas\Transfer\Exception\FormatResponse;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

use function sprintf;

class CouldNotAbandonPackageException extends RuntimeException implements PackagistServiceExceptionInterface
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
