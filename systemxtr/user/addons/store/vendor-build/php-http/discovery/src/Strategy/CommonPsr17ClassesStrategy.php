<?php

namespace Store\Dependency\Http\Discovery\Strategy;

use Store\Dependency\Psr\Http\Message\RequestFactoryInterface;
use Store\Dependency\Psr\Http\Message\ResponseFactoryInterface;
use Store\Dependency\Psr\Http\Message\ServerRequestFactoryInterface;
use Store\Dependency\Psr\Http\Message\StreamFactoryInterface;
use Store\Dependency\Psr\Http\Message\UploadedFileFactoryInterface;
use Store\Dependency\Psr\Http\Message\UriFactoryInterface;
/**
 * @internal
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 *
 * Don't miss updating src/Composer/Plugin.php when adding a new supported class.
 */
final class CommonPsr17ClassesStrategy implements DiscoveryStrategy
{
    /**
     * @var array
     */
    private static $classes = [RequestFactoryInterface::class => ['Store\Dependency\Phalcon\Http\Message\RequestFactory', 'Store\Dependency\Nyholm\Psr7\Factory\Psr17Factory', 'Store\Dependency\GuzzleHttp\Psr7\HttpFactory', 'Store\Dependency\Http\Factory\Diactoros\RequestFactory', 'Store\Dependency\Http\Factory\Guzzle\RequestFactory', 'Store\Dependency\Http\Factory\Slim\RequestFactory', 'Store\Dependency\Laminas\Diactoros\RequestFactory', 'Store\Dependency\Slim\Psr7\Factory\RequestFactory', 'Store\Dependency\HttpSoft\Message\RequestFactory'], ResponseFactoryInterface::class => ['Store\Dependency\Phalcon\Http\Message\ResponseFactory', 'Store\Dependency\Nyholm\Psr7\Factory\Psr17Factory', 'Store\Dependency\GuzzleHttp\Psr7\HttpFactory', 'Store\Dependency\Http\Factory\Diactoros\ResponseFactory', 'Store\Dependency\Http\Factory\Guzzle\ResponseFactory', 'Store\Dependency\Http\Factory\Slim\ResponseFactory', 'Store\Dependency\Laminas\Diactoros\ResponseFactory', 'Store\Dependency\Slim\Psr7\Factory\ResponseFactory', 'Store\Dependency\HttpSoft\Message\ResponseFactory'], ServerRequestFactoryInterface::class => ['Store\Dependency\Phalcon\Http\Message\ServerRequestFactory', 'Store\Dependency\Nyholm\Psr7\Factory\Psr17Factory', 'Store\Dependency\GuzzleHttp\Psr7\HttpFactory', 'Store\Dependency\Http\Factory\Diactoros\ServerRequestFactory', 'Store\Dependency\Http\Factory\Guzzle\ServerRequestFactory', 'Store\Dependency\Http\Factory\Slim\ServerRequestFactory', 'Store\Dependency\Laminas\Diactoros\ServerRequestFactory', 'Store\Dependency\Slim\Psr7\Factory\ServerRequestFactory', 'Store\Dependency\HttpSoft\Message\ServerRequestFactory'], StreamFactoryInterface::class => ['Store\Dependency\Phalcon\Http\Message\StreamFactory', 'Store\Dependency\Nyholm\Psr7\Factory\Psr17Factory', 'Store\Dependency\GuzzleHttp\Psr7\HttpFactory', 'Store\Dependency\Http\Factory\Diactoros\StreamFactory', 'Store\Dependency\Http\Factory\Guzzle\StreamFactory', 'Store\Dependency\Http\Factory\Slim\StreamFactory', 'Store\Dependency\Laminas\Diactoros\StreamFactory', 'Store\Dependency\Slim\Psr7\Factory\StreamFactory', 'Store\Dependency\HttpSoft\Message\StreamFactory'], UploadedFileFactoryInterface::class => ['Store\Dependency\Phalcon\Http\Message\UploadedFileFactory', 'Store\Dependency\Nyholm\Psr7\Factory\Psr17Factory', 'Store\Dependency\GuzzleHttp\Psr7\HttpFactory', 'Store\Dependency\Http\Factory\Diactoros\UploadedFileFactory', 'Store\Dependency\Http\Factory\Guzzle\UploadedFileFactory', 'Store\Dependency\Http\Factory\Slim\UploadedFileFactory', 'Store\Dependency\Laminas\Diactoros\UploadedFileFactory', 'Store\Dependency\Slim\Psr7\Factory\UploadedFileFactory', 'Store\Dependency\HttpSoft\Message\UploadedFileFactory'], UriFactoryInterface::class => ['Store\Dependency\Phalcon\Http\Message\UriFactory', 'Store\Dependency\Nyholm\Psr7\Factory\Psr17Factory', 'Store\Dependency\GuzzleHttp\Psr7\HttpFactory', 'Store\Dependency\Http\Factory\Diactoros\UriFactory', 'Store\Dependency\Http\Factory\Guzzle\UriFactory', 'Store\Dependency\Http\Factory\Slim\UriFactory', 'Store\Dependency\Laminas\Diactoros\UriFactory', 'Store\Dependency\Slim\Psr7\Factory\UriFactory', 'Store\Dependency\HttpSoft\Message\UriFactory']];
    public static function getCandidates($type)
    {
        $candidates = [];
        if (isset(self::$classes[$type])) {
            foreach (self::$classes[$type] as $class) {
                $candidates[] = ['class' => $class, 'condition' => [$class]];
            }
        }
        return $candidates;
    }
}
