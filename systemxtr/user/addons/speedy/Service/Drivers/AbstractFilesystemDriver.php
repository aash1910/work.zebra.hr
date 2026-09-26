<?php

namespace BoldMinded\Speedy\Service\Drivers;

use BoldMinded\Speedy\Library\Basee\Cache;
use BoldMinded\Speedy\Service\SpeedyRecursiveFilterIterator;
use Iterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use BoldMinded\Speedy\Service\CacheItem;
use RecursiveRegexIterator;
use RegexIterator;
use Throwable;

abstract class AbstractFilesystemDriver extends AbstractDriver
{
    protected string $cachePath;
    protected string $siteCachePath;
    public function __construct(string $cachePath)
    {
        parent::__construct();

        $this->cachePath = $cachePath;
        $this->siteCachePath = $cachePath . '/' . $this->siteName;

        if (!is_dir($this->siteCachePath) && !@mkdir($this->siteCachePath, DIR_WRITE_MODE, true)) {
            $this->getLogger()->error(sprintf('Site cache directory "%s" could not be created.', $this->siteCachePath));
        }
    }
    public function isConfigured(): bool
    {
        return true;
    }
    public function clear(): bool
    {
        return $this->clearCachePath($this->siteCachePath);
    }
    public function getItemsFromPath(string $path): array
    {
        $path = rtrim($this->siteCachePath . '/' . ltrim($path, '/'), '/');

        if (!@is_dir($path)) {
            return [];
        }

        $iterator = $this->getIterator($path, RecursiveIteratorIterator::LEAVES_ONLY);

        $items = [];
        $path_len = strlen($path . '/');
        $now = time();

        foreach ($iterator as $name => $file) {
            $name = str_replace('\\', '/', $name);
            // We can quickly filter out expired items based on the filemtime
            // which is set to the expiry time or 10 years in the future for
            // non-expiring items.
            if ($now < $file->getMTime()) {
                // Strip the $path prefix from each filename
                $items[] = $this->formatItemPathName(substr($name, $path_len));
            } else {
                // Use this opportunity to clear expired items
                @unlink($name);
            }
        }

        sort($items, SORT_STRING);

        return $items;
    }

    protected function formatItemPathName(string $name): string
    {
        return $name;
    }

    public function deleteItem(string $key): bool
    {
        if (CacheItem::isKeyRegex($key)) {
            return $this->deleteMatchingItems($key);
        }

        $file = $this->getFilename($key);

        try {
            if (!$file) { // https://boldminded.com/support/ticket/2926
                return false;
            }

            return !file_exists($file) || @unlink($file);
        } catch (\Exception $exception) {
            // Fail silently. @ isn't enough for PHP 8.
        }

        return false;
    }

    public function deleteMatchedItem(string $file): bool
    {
        try {
            if (!$file) {
                return false;
            }

            return !file_exists($file) || @unlink($file);
        } catch (\Exception $exception) {
            // Fail silently. @ isn't enough for PHP 8.
        }

        return false;
    }

    public function deleteMatchingItems(string $key): bool
    {
        // If we got here accidentally...
        if (!CacheItem::isKeyRegex($key)) {
            return $this->deleteItem($key);
        }

        $key = CacheItem::removeRegexIndicator($key);

        try {
            $directory = new RecursiveDirectoryIterator($this->siteCachePath);
            $iterator = new RecursiveIteratorIterator($directory);
            $regex = new RegexIterator(
                $iterator, '/'. str_replace('/', '\/', $key) .'/i',
                RecursiveRegexIterator::ALL_MATCHES
            );

            foreach ($regex as $file => $matches) {
                $this->deleteMatchedItem($file);
            }
        } catch (Throwable) {
            return false;
        }

        return false;
    }

    public function deletePath(string $path): bool
    {
        $file = $this->getFilename($path);

        return $this->clearCachePath($file);
    }

    protected function getFilename(string $key, bool $validateKey = true): string
    {
        if ($validateKey) {
            CacheItem::validateKey($key);
        }

        // Strip prefix as it is included in cache path
        $prefix = 'speedy/' . $this->siteName . '/';
        if (strpos($key, $prefix) === 0) {
            $key = substr($key, strlen($prefix));
        }

        return $this->siteCachePath . '/' . $key;
    }

    public function refresh(): bool
    {
        $now = $this->getCreatedAt();

        foreach ($this->getIterator() as $name => $file) {
            if ($now >= $file->getMTime()) {
                @unlink($name);
            }
        }

        return true;
    }

    public function writeFile(string $file, string $data, int $expiresAt = 3600): bool
    {
        // Attempt to create the path to the cache file.
        $path = dirname($file);

        if (!@mkdir($path, DIR_WRITE_MODE, true) && !is_dir($path)) {
            $this->getLogger()->error(sprintf('Could not create cache path "%s".', $path));

            return false;
        }

        // We set the filemtime to the expiry date so that expired cache items
        // can be easily detected without having to actually read the file.
        // Since non-expiring cache items are given a TTL of 0, we set a far
        // off filemtime of 10 years.
        if ($expiresAt === 0) {
            $expiresAt = time() + 315576000; // 10 years
        }

        // To prevent race conditions where multiple threads corrupt the cache
        // by writing to the same file at the same time we first write the data
        // out to a temp file.
        $tmpFile = $this->siteCachePath . '/' . uniqid('', true);

        $this->getLogger()->debug(sprintf('Writing data to temporary cache file name "%s".', $tmpFile));

        if (file_put_contents($tmpFile, $data) === false) {
            $this->getLogger()->error('Could not write data to temporary cache file.');

            return false;
        }

        $this->getLogger()->debug(sprintf('Renaming cache file to "%s".', $file));

        // Then use rename() (which is an atomic operation) to create the
        // cache data, or overwrite any existing file.
        if (@rename($tmpFile, $file)) {
            @chmod($file, 0644);
            @touch($file, $expiresAt);

            return true;
        }

        $this->getLogger()->error('Could not rename temporary cache file.');

        @unlink($tmpFile);

        return false;
    }

    protected function clearCachePath(string $path): bool
    {
        if (!@is_dir($path)) {
            return true;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $name => $file) {
            if ($file->isDir()) {
                @rmdir($name);
            } else {
                @unlink($name);
            }
        }

        if (file_exists($path)) {
            @rmdir($path);
        }

        return true;
    }

    protected function getIterator(string $path = '', int $flags = 0): Iterator
    {
        if ($path === '') {
            $path = $this->siteCachePath;
        }

        $iterator = new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS);
        $iterator = new SpeedyRecursiveFilterIterator($iterator);
        $iterator = new \RecursiveIteratorIterator($iterator, $flags);

        return $iterator;
    }

    public function getSiteCachePath(): string
    {
        return $this->siteCachePath;
    }

    public function getCachePath(): string
    {
        return $this->cachePath;
    }

    public function getDocumentRootPath(): string
    {
        return !empty($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : FCPATH;
    }
}
