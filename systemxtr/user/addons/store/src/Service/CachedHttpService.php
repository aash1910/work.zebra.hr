<?php

namespace Store\Service;

use Store\Dependency\GuzzleHttp\Client;
use Store\Dependency\GuzzleHttp\HandlerStack;
use Store\Dependency\GuzzleHttp\Psr7\Request;
use Store\Dependency\GuzzleHttp\Psr7\Response;
use Store\Guzzle\CacheStorage;
use Store\Dependency\GuzzleHttp\Promise\Promise;
use Store\Dependency\Psr\Http\Message\RequestInterface;
use Store\Dependency\Psr\Http\Message\ResponseInterface;
use Store\Dependency\Psr\SimpleCache\CacheInterface;

class CachedHttpService
{
    private Client $client;
    private CacheStorage $cache;

    public function __construct(?HandlerStack $handler = null, ?CacheInterface $cache = null)
    {
        $stack = $handler ?? HandlerStack::create();
        $this->cache = new CacheStorage($cache);

        // Add caching middleware
        $stack->push(function (callable $handler) {
            return function (RequestInterface $request, array $options) use ($handler) {
                $key = $this->cache->getCacheKey($request);
                $cached = $this->cache->getCache()->get($key);

                if ($cached !== null) {
                    $promise = new Promise();
                    $promise->resolve($cached);
                    return $promise;
                }

                return $handler($request, $options)->then(
                    function (ResponseInterface $response) use ($key) {
                        $this->cache->getCache()->set($key, $response, 3600);
                        return $response;
                    }
                );
            };
        });

        // Create the client with the handler stack
        $this->client = new Client([
            'handler' => $stack
        ]);
    }

    public function get($uri, array $options = [])
    {
        return $this->client->get($uri, $options);
    }

    public function post($uri, array $options = [])
    {
        return $this->client->post($uri, $options);
    }

    public function request($method, $uri, array $options = [])
    {
        return $this->client->request($method, $uri, $options);
    }

    public function getClient(): Client
    {
        return $this->client;
    }
}
