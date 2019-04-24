<?php

declare(strict_types=1);

namespace Laminas\Transfer\Exception;

use Psr\Http\Message\ResponseInterface;
use RuntimeException;

class ErrorAbandoningPackage extends RuntimeException implements ExceptionInterface
{
    use FormatResponseTrait;

    public static function forResponse(ResponseInterface $response, string $package) : self
    {
        return new self(sprintf(
            "The response from abandoning the package %s did not include the expected Location header:\n%s",
            $package,
            $this->serializeResponse($response)
        ), $response->getStatusCode());
    }
}
