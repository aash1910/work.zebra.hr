<?php

namespace Store\Dependency\Omnipay\AuthorizeNetApi;

/**
 *
 */
use Store\Dependency\Omnipay\Common\Exception\InvalidRequestException;
use Store\Dependency\Omnipay\AuthorizeNetApi\Message\AuthorizeRequest;
use Store\Dependency\Omnipay\AuthorizeNetApi\Message\PurchaseRequest;
use Store\Dependency\Omnipay\AuthorizeNetApi\Message\VoidRequest;
use Store\Dependency\Omnipay\AuthorizeNetApi\Message\RefundRequest;
use Store\Dependency\Omnipay\AuthorizeNetApi\Message\FetchTransactionRequest;
use Store\Dependency\Omnipay\AuthorizeNetApi\Message\AcceptNotification;
class ApiGateway extends AbstractGateway
{
    /**
     * The common name for this gateway driver API.
     */
    public function getName()
    {
        return 'Authorize.Net API';
    }
    /**
     * The authorization transaction.
     */
    public function authorize(array $parameters = [])
    {
        return $this->createRequest(AuthorizeRequest::class, $parameters);
    }
    /**
     * The purchase transaction.
     */
    public function purchase(array $parameters = [])
    {
        return $this->createRequest(PurchaseRequest::class, $parameters);
    }
    /**
     * Void an authorized transaction.
     */
    public function void(array $parameters = [])
    {
        return $this->createRequest(VoidRequest::class, $parameters);
    }
    /**
     * Refund a captured transaction (before it is cleared).
     */
    public function refund(array $parameters = [])
    {
        return $this->createRequest(RefundRequest::class, $parameters);
    }
    /**
     * Fetch an existing transaction details.
     */
    public function fetchTransaction(array $parameters = [])
    {
        return $this->createRequest(FetchTransactionRequest::class, $parameters);
    }
    /**
     * Accept a notification.
     */
    public function acceptNotification(array $parameters = [])
    {
        return $this->createRequest(AcceptNotification::class, $parameters);
    }
}
