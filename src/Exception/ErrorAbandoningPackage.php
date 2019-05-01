<?php

declare(strict_types=1);

namespace Laminas\Transfer\Exception;

use Psr\Http\Message\ResponseInterface;
use RuntimeException;

class ErrorAbandoningPackage extends RuntimeException implements ExceptionInterface
{
    public static function forResponse(ResponseInterface $response, string $package) : self
    {
        return new self(sprintf(
            "The response from abandoning the package %s did not include the expected Location header:\n%s",
            $package,
            FormatResponse::serializeResponse($response)
        ), $response->getStatusCode());
    }
}
