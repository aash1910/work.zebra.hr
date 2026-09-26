<?php

namespace Store\Guzzle;

use Store\Dependency\Psr\Http\Message\RequestInterface;
use Store\Dependency\Psr\Http\Message\ResponseInterface;
use Store\Dependency\Psr\SimpleCache\CacheInterface;

/**
 * Custom cache storage implementation
 *
 * Overrides default Guzzle cache to take into account
 * request body when caching requests.
 */
class CacheStorage
{
    private CacheInterface $cache;

    public function __construct(?CacheInterface $cache = null)
    {
        $this->cache = $cache ?? ee()->store->cache;
    }

    /**
     * Provides access to the internal cache
     */
    public function getCache(): CacheInterface
    {
        return $this->cache;
    }

    /**
     * Hash a request into a string that returns cache metadata
     *
     * @return string
     */
    public function getCacheKey(RequestInterface $request): string
    {
        $parts = [
            $request->getMethod(),
            $request->getUri()->__toString(),
        ];

        $body = (string) $request->getBody();
        if (!empty($body)) {
            $parts[] = md5($body);
        }

        return md5(implode('|', $parts));
    }
}
