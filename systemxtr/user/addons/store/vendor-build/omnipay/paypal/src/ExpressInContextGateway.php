<?php

namespace Store\Dependency\Omnipay\PayPal;

/**
 * PayPal Express In-Context Class
 */
class ExpressInContextGateway extends ExpressGateway
{
    public function getName()
    {
        return 'PayPal Express In-Context';
    }
    public function authorize(array $parameters = array())
    {
        return $this->createRequest('Store\Dependency\Omnipay\PayPal\Message\ExpressInContextAuthorizeRequest', $parameters);
    }
    public function order(array $parameters = array())
    {
        return $this->createRequest('Store\Dependency\Omnipay\PayPal\Message\ExpressInContextOrderRequest', $parameters);
    }
}
