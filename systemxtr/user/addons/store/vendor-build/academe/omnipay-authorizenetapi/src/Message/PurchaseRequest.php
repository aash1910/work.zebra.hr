<?php

namespace Store\Dependency\Omnipay\AuthorizeNetApi\Message;

use Store\Dependency\Academe\AuthorizeNet\AmountInterface;
use Store\Dependency\Academe\AuthorizeNet\Request\Transaction\AuthCapture;
class PurchaseRequest extends AuthorizeRequest
{
    /**
     * Create a new instance of the transaction object.
     */
    protected function createTransaction(AmountInterface $amount)
    {
        return new AuthCapture($amount);
    }
}
