<?php

namespace BoldMinded\Speedy\Service\Request;

use BoldMinded\Speedy\Service\Request\Driver\CurlAsyncRequestDriver;
use BoldMinded\Speedy\Service\Request\Driver\CurlExecRequestDriver;
use BoldMinded\Speedy\Service\Request\Driver\CurlSyncRequestDriver;
use BoldMinded\Speedy\Service\Request\Driver\StreamAsyncRequestDriver;
use BoldMinded\Speedy\Service\Request\Driver\StreamSyncRequestDriver;

class Facade
{
    /** @var \BoldMinded\Speedy\Service\Request\RequestDriver[] */
    private $drivers = [];

    /**
     * Request constructor.
     */
    public function __construct()
    {
        if (CurlAsyncRequestDriver::isSupported()) {
            $this->drivers[] = new CurlAsyncRequestDriver();
        }
        if (CurlExecRequestDriver::isSupported()) {
            $this->drivers[] = new CurlExecRequestDriver();
        }
        if (CurlSyncRequestDriver::isSupported()) {
            $this->drivers[] = new CurlSyncRequestDriver();
        }
        if (StreamAsyncRequestDriver::isSupported()) {
            $this->drivers[] = new StreamAsyncRequestDriver();
        }
        if (StreamSyncRequestDriver::isSupported()) {
            $this->drivers[] = new StreamSyncRequestDriver();
        }
    }

    /**
     * @return bool
     */
    public function isSupported()
    {
        return count($this->drivers) > 0;
    }

    /**
     * @param string $url
     * @return bool
     */
    public function get($url)
    {
        foreach ($this->drivers as $driver) {
            if ($driver->get($url)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $class
     * @param string $method
     * @param array  $params
     * @return bool
     */
    public function getAction($class, $method, $params = [])
    {
        $action_id = $this->fetchActionId($class, $method);

        if ($action_id === false) {
            return false;
        }

        $url = $this->getActionUrl($class, $method, $params);

        if (!$url) {
            return false;
        }

        return $this->get($url);
    }

    /**
     * @param string    $class
     * @param string    $method
     * @param array     $params
     * @return string
     */
    public function getActionUrl($class, $method, $params = [])
    {
        $action_id = $this->fetchActionId($class, $method);

        if ($action_id === false) {
            return false;
        }

        $site_index = ee()->functions->fetch_site_index(false, false);
        $additional = rtrim('&' . http_build_query($params, '', '&'), '&');

        return $site_index . '?ACT=' . $action_id . $additional;
    }

    /**
     * @param string $class
     * @param string $method
     * @return int|bool
     */
    private function fetchActionId($class, $method)
    {
        /** @var \ExpressionEngine\Model\Addon\Action $action */
        $action = ee('Model')->get('Action')
            ->filter('class', '=', $class)
            ->filter('method', '=', $method)
            ->first();

        if ($action === null) {
            return false;
        }

        return $action->action_id;
    }
}
