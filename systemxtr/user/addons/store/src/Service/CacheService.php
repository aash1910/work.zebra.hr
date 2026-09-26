<?php

namespace Store\Service;

use Store\Dependency\Psr\SimpleCache\CacheInterface;

class CacheService extends AbstractService implements CacheInterface
{
    public $prefix = '/store/';

    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->ee->cache->get($this->prefix . $key);
        return $value === false ? $default : $value;
    }

    public function set(string $key, mixed $value, null|int|\DateInterval $ttl = null): bool
    {
        return $this->ee->cache->save($this->prefix . $key, $value, $ttl ?? 3600);
    }

    public function delete(string $key): bool
    {
        return $this->ee->cache->delete($this->prefix . $key);
    }

    public function clear(): bool
    {
        // Not implemented as we don't want to clear the entire cache
        return false;
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get($key, $default);
        }
        return $result;
    }

    public function setMultiple(iterable $values, null|int|\DateInterval $ttl = null): bool
    {
        $success = true;
        foreach ($values as $key => $value) {
            $success = $this->set($key, $value, $ttl) && $success;
        }
        return $success;
    }

    public function deleteMultiple(iterable $keys): bool
    {
        $success = true;
        foreach ($keys as $key) {
            $success = $this->delete($key) && $success;
        }
        return $success;
    }

    public function has(string $key): bool
    {
        return $this->ee->cache->get($this->prefix . $key) !== false;
    }
}
