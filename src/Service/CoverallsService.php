<?php

declare(strict_types=1);

namespace Laminas\Transfer\Service;

use Fig\Http\Message\RequestMethodInterface as Method;
use Laminas\Transfer\Exception;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamFactoryInterface;

class CoverallsService
{
    private const URI_BASE = 'https://coveralls.io';

    private const URI_ACTIVATE = '/api/repos';

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

    /**
     * @var StreamFactoryInterface
     */
    private $streamFactory;

    public function __construct(
        string $apiToken,
        ClientInterface $httpClient,
        RequestFactoryInterface $requestFactory,
        StreamFactoryInterface $streamFactory
    ) {
        $this->apiToken       = $apiToken;
        $this->httpClient     = $httpClient;
        $this->requestFactory = $requestFactory;
        $this->streamFactory  = $streamFactory;
    }

    /**
     * @throws Exception\ErrorActivatingCoveralls if the API returns an error
     */
    public function activate(string $repository) : void
    {

        $request = $this->createRequest(Method::METHOD_POST, self::URI_ACTIVATE)
            ->withBody($this->streamFactory->createStream(json_encode([
                'repo' => [
                    'service'                             => 'github',
                    'name'                                => $repository,
                    'comment_on_pull_requests'            => false,
                    'send_build_status'                   => true,
                    'commit_status_fail_threshold'        => 80.0,
                    'commit_status_fail_change_threshold' => 3.0,
                ],
            ])));

        $response = $client->sendRequest($request);

        if (201 !== $response->getStatusCode()) {
            throw Exception\ErrorActivatingCoveralls::forResponse($response, $repository);
        }
    }

    private function createRequest(string $method, string $path) : RequestInterface
    {
        return $this->requestFactory->createRequest($method, sprintf('%s%s', self::URI_BASE, $path))
            ->withHeader('Accept', 'application/json')
            ->withHeader('Authorization', sprintf('token %s', $this->apiToken));
    }
}
