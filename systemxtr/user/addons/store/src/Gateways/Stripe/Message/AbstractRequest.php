<?php

namespace Store\Gateways\Stripe\Message;

use Store\Dependency\Omnipay\Common\Message\AbstractRequest as OmnipayAbstractRequest;

/**
 * Stripe Abstract Request
 */
abstract class AbstractRequest extends OmnipayAbstractRequest
{
    /**
     * Live Endpoint URL
     *
     * @var string URL
     */
    protected $liveEndpoint = 'https://api.stripe.com/v1';

    /**
     * Get the API Key
     *
     * @return string
     */
    public function getApiKey()
    {
        return $this->getParameter('apiKey');
    }

    /**
     * Set the API Key
     *
     * @param string $value
     * @return AbstractRequest provides a fluent interface.
     */
    public function setApiKey($value)
    {
        return $this->setParameter('apiKey', $value);
    }

    /**
     * Get the API version
     *
     * @return string
     */
    public function getApiVersion()
    {
        return $this->getParameter('apiVersion');
    }

    /**
     * Set the API version
     *
     * @param string $value
     * @return AbstractRequest provides a fluent interface.
     */
    public function setApiVersion($value)
    {
        return $this->setParameter('apiVersion', $value);
    }

    /**
     * Get the endpoint URL for the request.
     *
     * @return string
     */
    public function getEndpoint()
    {
        return $this->liveEndpoint;
    }

    /**
     * Set payment method
     *
     * @param string $value
     * @return AbstractRequest provides a fluent interface.
     */
    public function setPaymentMethod($value)
    {
        return $this->setParameter('paymentMethod', $value);
    }

    /**
     * Get payment method
     *
     * @return string
     */
    public function getPaymentMethod()
    {
        return $this->getParameter('paymentMethod');
    }

    /**
     * @return array
     */
    public function getHeaders()
    {
        $headers = [
            'Authorization' => 'Bearer ' . $this->getApiKey(),
            'Stripe-Version' => $this->getApiVersion() ?: '2023-10-16',
            'Content-Type' => 'application/x-www-form-urlencoded',
        ];

        return $headers;
    }

    /**
     * Get headers specifically for JSON requests
     *
     * @return array
     */
    public function getJsonHeaders()
    {
        $headers = [
            'Authorization' => 'Bearer ' . $this->getApiKey(),
            'Stripe-Version' => $this->getApiVersion() ?: '2023-10-16',
            'Content-Type' => 'application/json',
        ];

        return $headers;
    }

    protected function createResponse($data, $statusCode = 200)
    {
        return $this->response = new Response($this, $data, $statusCode);
    }
}
