<?php

namespace Store\Dependency\Omnipay\Common\Http;

use Store\Dependency\Omnipay\Common\Http\Exception\NetworkException;
use Store\Dependency\Omnipay\Common\Http\Exception\RequestException;
use Store\Dependency\Psr\Http\Message\ResponseInterface;
use Store\Dependency\Psr\Http\Message\StreamInterface;
use Store\Dependency\Psr\Http\Message\UriInterface;
interface ClientInterface
{
    /**
     * Creates a new PSR-7 request.
     *
     * @param string                               $method
     * @param string|UriInterface                  $uri
     * @param array                                $headers
     * @param resource|string|StreamInterface|null $body
     * @param string                               $protocolVersion
     *
     * @throws RequestException when the HTTP client is passed a request that is invalid and cannot be sent.
     * @throws NetworkException if there is an error with the network or the remote server cannot be reached.
     *
     * @return ResponseInterface
     */
    public function request($method, $uri, array $headers = [], $body = null, $protocolVersion = '1.1');
}
