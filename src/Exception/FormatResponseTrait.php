<?php

declare(strict_types=1);

namespace Laminas\Transfer\Exception;

use Psr\Http\Message\ResponseInterface;
use Zend\Diactoros\Response\Serializer as ResponseSerializer;

trait FormatResponseTrait
{
    private function serializeResponse(ResponseInterface $response) : string
    {
        return ResponseSerializer::toString($response);
    }
}
