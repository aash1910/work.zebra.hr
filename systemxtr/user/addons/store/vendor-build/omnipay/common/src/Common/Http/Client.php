<?php

namespace Store\Dependency\Omnipay\Common\Http;

use Store\Dependency\Http\Client\HttpClient;
use Store\Dependency\Http\Discovery\HttpClientDiscovery;
use Store\Dependency\Http\Discovery\MessageFactoryDiscovery;
use Store\Dependency\Http\Message\RequestFactory;
use Store\Dependency\Omnipay\Common\Http\Exception\NetworkException;
use Store\Dependency\Omnipay\Common\Http\Exception\RequestException;
use Store\Dependency\Psr\Http\Message\RequestInterface;
use Store\Dependency\Psr\Http\Message\ResponseInterface;
use Store\Dependency\Psr\Http\Message\StreamInterface;
class Client implements ClientInterface
{
    /**
     * The Http Client which implements `public function sendRequest(RequestInterface $request)`
     * Note: Will be changed to PSR-18 when released
     *
     * @var HttpClient
     */
    private $httpClient;
    /**
     * @var RequestFactory
     */
    private $requestFactory;
    public function __construct($httpClient = null, ?RequestFactory $requestFactory = null)
    {
        $this->httpClient = $httpClient ?: HttpClientDiscovery::find();
        $this->requestFactory = $requestFactory ?: MessageFactoryDiscovery::find();
    }
    /**
     * @param $method
     * @param $uri
     * @param array $headers
     * @param string|array|resource|StreamInterface|null $body
     * @param string $protocolVersion
     * @return ResponseInterface
     * @throws \Http\Client\Exception
     */
    public function request($method, $uri, array $headers = [], $body = null, $protocolVersion = '1.1')
    {
        $request = $this->requestFactory->createRequest($method, $uri, $headers, $body, $protocolVersion);
        return $this->sendRequest($request);
    }
    /**
     * @param RequestInterface $request
     * @return ResponseInterface
     * @throws \Http\Client\Exception
     */
    private function sendRequest(RequestInterface $request)
    {
        try {
            return $this->httpClient->sendRequest($request);
        } catch (\Store\Dependency\Http\Client\Exception\NetworkException $networkException) {
            throw new NetworkException($networkException->getMessage(), $request, $networkException);
        } catch (\Exception $exception) {
            throw new RequestException($exception->getMessage(), $request, $exception);
        }
    }
}
