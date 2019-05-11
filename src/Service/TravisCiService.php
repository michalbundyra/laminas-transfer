<?php

declare(strict_types=1);

namespace Laminas\Transfer\Service;

use Fig\Http\Message\RequestMethodInterface as Method;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;

use function sprintf;
use function urlencode;

class TravisCiService
{
    private const API_VERSION = 3;

    private const URI_BASE = 'https://api.travis-ci.org';

    private const URI_ACTIVATE = '/repo/%s/activate';

    private const USER_AGENT = 'laminas/http-client';

    /**
     * @var string
     */
    private $apiToken;

    /**
     * @var ClientInterface
     */
    private $httpClient;

    /**
     * @var RequestFactoryInterface
     */
    private $requestFactory;

    public function __construct(
        string $apiToken,
        ClientInterface $httpClient,
        RequestFactoryInterface $requestFactory
    ) {
        $this->apiToken = $apiToken;
        $this->httpClient = $httpClient;
        $this->requestFactory = $requestFactory;
    }

    /**
     * Activate Travis-CI builds for the given repository.
     */
    public function activate(string $repository) : void
    {
        $response = $this->httpClient->sendRequest(
            $this->createRequest(Method::METHOD_POST, sprintf(self::URI_ACTIVATE, urlencode($repository)))
        );
    }

    private function createRequest(string $method, string $path) : RequestInterface
    {
        return $this->requestFactory->createRequest($method, sprintf('%s%s', self::URI_BASE, $path))
                                    ->withHeader('Accept', 'application/json')
                                    ->withHeader('Travis-API-Version', self::API_VERSION)
                                    ->withHeader('User-Agent', self::USER_AGENT)
                                    ->withHeader('Authorization', sprintf('token %s', $this->apiToken));
    }
}
