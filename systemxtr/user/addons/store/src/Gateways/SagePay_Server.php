<?php

/*
 * Exp:resso Store module for ExpressionEngine
 * Copyright (c) 2010-2019 Exp:resso (support@exp-resso.com)
 */

namespace Store\Gateways;

use Store\Dependency\Omnipay\Common\Message\RequestInterface;
use Store\Dependency\Omnipay\SagePay\ServerGateway;
use Store\Model\Transaction;
use Store\Transformer\AbstractTransformer;
use Store\Transformer\SagePayTransformer;

/**
 * This is a SagePay Server override class that will allow it to work more seamlessly with Exp:resso Store
 * @method RequestInterface updateCard(array $options = array())
 */
class SagePay_Server extends ServerGateway
{
    public function getShortName()
    {
        return 'SagePay_Server';
    }

    /**
     * @param array $params
     * @return array$params
     */
    public function setParamReference($params, Transaction $transaction)
    {
        $params['transactionReference'] = $transaction->reference;

        return $params;
    }

    /**
     * @return AbstractTransformer
     */
    public function getTransformer()
    {
        return new SagePayTransformer();
    }
}
