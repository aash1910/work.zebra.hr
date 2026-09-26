<?php

namespace Store\Dependency\Omnipay\SagePay;

use Store\Dependency\Omnipay\SagePay\Message\Form\AuthorizeRequest;
use Store\Dependency\Omnipay\SagePay\Message\Form\CompleteAuthorizeRequest;
use Store\Dependency\Omnipay\SagePay\Message\Form\CompletePurchaseRequest;
use Store\Dependency\Omnipay\SagePay\Message\Form\PurchaseRequest;
/**
 * Sage Pay Server Gateway
 */
class FormGateway extends AbstractGateway
{
    public function getName()
    {
        return 'Sage Pay Form';
    }
    /**
     * Authorize a payment.
     */
    public function authorize(array $parameters = array())
    {
        return $this->createRequest(AuthorizeRequest::class, $parameters);
    }
    /**
     * Authorize and capture a payment.
     */
    public function purchase(array $parameters = array())
    {
        return $this->createRequest(PurchaseRequest::class, $parameters);
    }
    /**
     *
     */
    public function completeAuthorize(array $parameters = array())
    {
        return $this->createRequest(CompleteAuthorizeRequest::class, $parameters);
    }
    /**
     *
     */
    public function completePurchase(array $parameters = array())
    {
        return $this->createRequest(CompletePurchaseRequest::class, $parameters);
    }
}
