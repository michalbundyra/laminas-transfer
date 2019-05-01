<?php

declare(strict_types=1);

namespace Laminas\Transfer\Service;

use Dflydev\FigCookies\Cookie;
use Dflydev\FigCookies\FigResponseCookies;
use DOMDocument;
use DOMElement;
use Fig\Http\Message\RequestMethodInterface as Method;
use Fig\Http\Message\StatusCodeInterface as StatusCode;
use Laminas\Transfer\Exception;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

use function http_build_query;
use function json_encode;
use function sprintf;

/**
 * Interact with the Packagist API
 *
 * - create packages
 * - abandon packages
 */
class PackagistService
{
    /* @phpcs:disable */
    private const ACCEPT_BROWSER = 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8';
    /* @phpcs:enable */

    private const COOKIE_NAME = 'packagist';

    private const MIME_TYPE_FORM = 'application/x-www-form-urlencoded';

    private const MIME_TYPE_JSON = 'application/json';

    private const PACKAGE_TOKEN_ELEMENT_ID = 'package__token';

    private const URI_LOGIN = 'https://packagist.org/login_check';

    private const URI_PACKAGE = 'https://packagist.org/packages/%s';

    private const URI_PACKAGE_ABANDON = 'https://packagist.org/packages/%s/abandon';

    private const URI_PACKAGE_CREATE = 'https://packagist.org/api/create-package';

    private const URI_REFERER = 'https://packagist.org';

    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * @var string
     */
    private $packagistApiToken;

    /**
     * @var string
     */
    private $packagistPassword;

    /**
     * @var string
     */
    private $packagistUsername;

    /**
     * @var RequestFactoryInterface
     */
    private $requestFactory;

    /**
     * @var StreamFactoryInterface
     */
    private $streamFactory;

    public function __construct(
        ClientInterface $client,
        RequestFactoryInterface $requestFactory,
        StreamFactoryInterface $streamFactory,
        string $packagistUsername,
        string $packagistPassword,
        string $packagistApiToken
    ) {
        $this->client = $client;
        $this->requestFactory = $requestFactory;
        $this->streamFactory = $streamFactory;
        $this->packagistUsername = $packagistUsername;
        $this->packagistPassword = $packagistPassword;
        $this->packagistApiToken = $packagistApiToken;
    }

    /**
     * Create a new package on Packagist.
     *
     * POST https://packagist.org/api/create-package?username=<username>&apiToken=<apiToken>
     *
     * Where the payload is of the form:
     *
     * <code class="json">
     * {
     *     "repository": {
     *         "url": "<url of repository>"
     *     }
     * }
     * </code>
     *
     * @todo error handling
     * @todo detect error response
     */
    public function createPackage(string $packageUrl) : void
    {
        $request = $this->requestFactory
                        ->createRequest(Method::METHOD_POST, self::URI_PACKAGE_CREATE)
                        ->withHeader('Content-Type', self::MIME_TYPE_JSON)
                        ->withHeader('Accept', self::MIME_TYPE_JSON);

        $uri = $request->getUri();
        $uri = $uri->withQuery(http_build_query([
            'username' => $this->packagistUsername,
            'apiToken' => $this->packagistApiToken,
        ]));

        $body = $this->streamFactory->createStream(json_encode([
            'repository' => [
                'url' => $packageUrl,
            ],
        ]));

        $request = $request
            ->withUri($uri)
            ->withBody($body);

        $response = $this->client->sendRequest($request);
    }

    /**
     * Abandon a package on Packagist
     *
     * Abandons a package on Packagist, suggesting a new package as a replacement.
     *
     * Requires the following steps:
     *
     * - POST https://packagist.org/login_check _username=<username> _password=<password> referer:https://packagist.org
     *   Retrieve the "packagist" cookie value
     * - GET https://packagist.org/packages/<package>/abandon "Cookie:packagist=<cookie>"
     *   Retrieve the value of the #package__token element
     * - POST https://packagist.org/packages/<package>/abandon
     *   "Cookie:packagist=<cookie>"
     *   "package[replacement]=<new package>"
     *   "package[_token]=<token>"
     *
     * On success, this latter will return a 302 with a Location header pointing to /packages/<package>
     *
     * @param string $originalPackage The package name; e.g. zendframework/zend-stdlib
     * @param string $newPackage The replacement package, if any. Use an empty
     *     string to indicate no replacement.
     * @throws CouldNotAbandonPackage if the response does not indicate success
     *     in abandoning the package.
     * @throws ErrorAbandoningPackage if the response does not include a
     *     Location header leading back to the original package URL.
     */
    public function abandonPackage(string $originalPackage, string $newPackage) : void
    {
        $url = sprintf(self::URI_PACKAGE_ABANDON, $originalPackage);
        $cookie = $this->getLoginCookie();
        $token = $this->getPackageToken($url, $cookie);

        $body = $this->streamFactory->createStream(http_build_query([
            'package' => [
                'replacement' => $newPackage,
                '_token' => $token,
            ],
        ]));

        $request = $this->requestFactory
                        ->createRequest(Method::METHOD_POST, $url)
                        ->withHeader('Cookie', (string) $cookie)
                        ->withHeader('Content-Type', self::MIME_TYPE_FORM)
                        ->withHeader('Accept', self::ACCEPT_BROWSER)
                        ->withBody($body);

        $response = $this->client->sendRequest($request);

        if ($response->getStatusCode() !== StatusCode::STATUS_FOUND) {
            throw Exception\CouldNotAbandonPackage::forResponse($response, $originalPackage);
        }

        $location = $response->getHeaderLine('Location');
        if ($location !== sprintf(self::URI_PACKAGE, $originalPackage)) {
            throw Exception\ErrorAbandoningPackage::forResponse($response, $originalPackage);
        }
    }

    /**
     * @throws ErrorLoggingIn if an unexpected response code occurs
     * @throws NoCookieReturnedDuringLogin if the response does not include the
     *     expected cookie
     */
    private function getLoginCookie() : Cookie
    {
        $body = $this->streamFactory->createStream(http_build_query([
            '_username' => $this->packagistUsername,
            '_password' => $this->packagistPassword,
        ]));

        $request = $this->requestFactory
                        ->createRequest(Method::METHOD_POST, self::URI_LOGIN)
                        ->withHeader('Referer', self::URI_REFERER)
                        ->withHeader('Content-Type', self::MIME_TYPE_FORM)
                        ->withHeader('Accept', self::ACCEPT_BROWSER)
                        ->withBody($body);

        $response = $this->client->sendRequest($request);

        if ($response->getStatusCode() !== StatusCode::STATUS_FOUND) {
            throw Exception\ErrorLoggingIn::forResponse($response);
        }

        $cookie = FigResponseCookies::get($response, self::COOKIE_NAME);
        if (! $cookie) {
            throw Exception\NoCookieReturnedDuringLogin::forResponse($response);
        }

        return Cookie::create(self::COOKIE_NAME, $cookie->getValue());
    }

    /**
     * @throws CannotRetrieveAbandonPackageToken
     * @throws CannotLocateAbandonPackageToken
     */
    private function getPackageToken(string $url, Cookie $cookie) : string
    {
        $request = $this->requestFactory
                        ->createRequest(Method::METHOD_GET, $url)
                        ->withHeader('Cookie', (string) $cookie)
                        ->withHeader('Accept', self::ACCEPT_BROWSER);

        $response = $this->client->sendRequest($request);

        if ($response->getStatusCode() !== StatusCode::STATUS_OK) {
            throw Exception\CannotRetrieveAbandonPackageToken::forResponse($response, $url);
        }

        $content = (string) $response->getBody();

        $dom = new DOMDocument();
        $dom->loadHTML($content);
        $tokenElement = $dom->getElementById(self::PACKAGE_TOKEN_ELEMENT_ID);
        if (! $tokenElement instanceof DOMElement) {
            throw Exception\CannotLocateAbandonPackageToken::forResponse($response, $url);
        }

        return $tokenElement->getAttribute('value');
    }
}
