<?php

namespace BoldMinded\Speedy\Service\Request\Driver;

use BoldMinded\Speedy\Service\Request\RequestDriver;

abstract class AbstractStreamRequestDriver implements RequestDriver
{
    /**
     * @param string $url
     * @return bool
     */
    public function get($url)
    {
        $url_parts = @parse_url($url);

        if ($url_parts === false) {
            return false;
        }

        $sh = $this->buildSocketClient($url_parts, $errno, $errstr);

        if ($sh === false || $errno !== 0) {
            return false;
        }

        @stream_set_timeout($sh, $this->getSocketTimeout());
        @socket_set_blocking($sh, $this->getSocketBlocking());
        @fwrite($sh, $this->buildRequestContent($url_parts));

        if ($this->getSocketBlocking()) {
            while (!feof($sh)) {
                fread($sh, 4096);
            }
        }

        @fclose($sh);

        return true;
    }

    /**
     * @param array $url_parts
     * @return string
     */
    protected function getSocketScheme(array $url_parts)
    {
        if (isset($url_parts['scheme']) && strtolower($url_parts['scheme']) === 'https') {
            return 'ssl://';
        }

        return 'tcp://';
    }

    /**
     * @param array $url_parts
     * @return string
     */
    protected function getSocketHost(array $url_parts)
    {
        return $url_parts['host'];
    }

    /**
     * @param array $url_parts
     * @return int
     */
    protected function getSocketPort(array $url_parts)
    {
        if (isset($url_parts['port'])) {
            return (int) $url_parts['port'];
        }

        if (isset($url_parts['scheme']) && strtolower($url_parts['scheme']) === 'https') {
            return 443;
        }

        return 80;
    }

    /**
     * @return array
     */
    protected function getSocketContextOptions()
    {
        return [
            'ssl' => [
                'verify_peer'      => false,
                'verify_peer_name' => false,
            ],
        ];
    }

    /**
     * @param array  $url_parts
     * @param int    $errno
     * @param string $errstr
     * @return resource
     */
    protected function buildSocketClient(array $url_parts, &$errno, &$errstr)
    {
        $scheme = $this->getSocketScheme($url_parts);
        $host = $this->getSocketHost($url_parts);
        $port = $this->getSocketPort($url_parts);

        $flags = $this->getSocketFlags();
        $timeout = $this->getSocketTimeout();
        $options = $this->getSocketContextOptions();

        $remote_socket = $scheme . $host . ':' . $port;
        $context = stream_context_create($options);

        return @stream_socket_client($remote_socket, $errno, $errstr, $timeout, $flags, $context);
    }

    /**
     * @return int
     */
    abstract protected function getSocketFlags();

    /**
     * @return int
     */
    abstract protected function getSocketTimeout();

    /**
     * @return bool
     */
    abstract protected function getSocketBlocking();

    /**
     * @param array $url_parts
     * @return string
     */
    protected function getRequestHost(array $url_parts)
    {
        return $this->getSocketHost($url_parts);
    }

    /**
     * @param array $url_parts
     * @return string
     */
    protected function getRequestPort(array $url_parts)
    {
        return $this->getSocketPort($url_parts);
    }

    /**
     * @param array $url_parts
     * @return string
     */
    protected function getRequestPath(array $url_parts)
    {
        return isset($url_parts['path']) ? $url_parts['path'] : '/';
    }

    /**
     * @param array $url_parts
     * @return string
     */
    protected function getRequestQuery(array $url_parts)
    {
        return isset($url_parts['query']) ? '?' . $url_parts['query'] : '';
    }

    /**
     * @param array $url_parts
     * @return string
     */
    protected function buildRequestContent(array $url_parts)
    {
        $host = $this->getRequestHost($url_parts);
        $port = $this->getRequestPort($url_parts);
        $path = $this->getRequestPath($url_parts);
        $query = $this->getRequestQuery($url_parts);

        $req = sprintf("GET %s%s HTTP/1.1\r\n", $path, $query);
        $req .= sprintf("Host: %s:%d\r\n", $host, $port);
        $req .= sprintf("User-Agent: %s\r\n", RequestDriver::USER_AGENT);
        $req .= "Connection: Close\r\n\r\n";

        return $req;
    }
}
