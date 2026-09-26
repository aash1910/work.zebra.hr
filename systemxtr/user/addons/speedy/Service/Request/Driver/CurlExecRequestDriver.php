<?php

namespace BoldMinded\Speedy\Service\Request\Driver;

use BoldMinded\Speedy\Service\Request\RequestDriver;
use BoldMinded\Speedy\Service\Supported\SupportedValidator;

class CurlExecRequestDriver implements RequestDriver
{
    /**
     * @return bool
     */
    public static function isSupported()
    {
        return self::getSupportValidator()->isSupported();
    }

    /**
     * @return \BoldMinded\Speedy\Service\Supported\SupportedValidator
     */
    public static function getSupportValidator()
    {
        return SupportedValidator::make()
            ->checkConfigNotEquals('speedy_refresh_curl', 'no')
            ->checkConfigNotEquals('speedy_refresh_exec', 'no')
            ->checkConfigNotEquals('speedy_refresh_async', 'no')
            ->checkFunctionUsable('exec');
    }

    /**
     * @param string $url
     * @return bool
     */
    public function get($url)
    {
        $command = sprintf('curl -X GET %s -k -m 5 > /dev/null 2>&1 &', escapeshellarg($url));

        exec($command);

        return true;
    }
}
