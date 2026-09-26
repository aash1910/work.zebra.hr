<?php

/**
 * Manual Gateway Request
 */
namespace Store\Dependency\Omnipay\Manual\Message;

use Store\Dependency\Omnipay\Common\Message\AbstractRequest;
/**
 * Manual Gateway Request
 */
class Request extends AbstractRequest
{
    public function getData()
    {
        $this->validate('amount');
        return $this->getParameters();
    }
    public function sendData($data)
    {
        return $this->response = new Response($this, $data);
    }
}
