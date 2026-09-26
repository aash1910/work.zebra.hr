<?php

namespace Store\Dependency\Omnipay\SagePay\Message\Shared;

/**
 * Sage Pay fetch a transaction.
 * Reporting command: getTransactionDetail
 */
use Store\Dependency\Omnipay\SagePay\Message\AbstractRequest;
class FetchTransaction extends AbstractRequest
{
    // TODO: this is an XML interface completely different to the payments APIs.
}
