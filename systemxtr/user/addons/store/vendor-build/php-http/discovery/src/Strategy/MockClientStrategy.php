<?php

namespace Store\Dependency\Http\Discovery\Strategy;

use Store\Dependency\Http\Client\HttpAsyncClient;
use Store\Dependency\Http\Client\HttpClient;
use Store\Dependency\Http\Mock\Client as Mock;
/**
 * Find the Mock client.
 *
 * @author Sam Rapaport <me@samrapdev.com>
 */
final class MockClientStrategy implements DiscoveryStrategy
{
    public static function getCandidates($type)
    {
        if (is_a(HttpClient::class, $type, \true) || is_a(HttpAsyncClient::class, $type, \true)) {
            return [['class' => Mock::class, 'condition' => Mock::class]];
        }
        return [];
    }
}
