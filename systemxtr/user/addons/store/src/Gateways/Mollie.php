<?php

/*
 * Exp:resso Store module for ExpressionEngine
 * Copyright (c) 2010-2019 Exp:resso (support@exp-resso.com)
 */

namespace Store\Gateways;

use Store\Dependency\Omnipay\Mollie\Gateway;
use Store\Model\Transaction;
use Store\Transformer\AbstractTransformer;
use Store\Transformer\DefaultTransformer;

/**
 * This is a Mollie override class that will allow it to work more seamlessly with Exp:resso Store
 */
class Mollie extends Gateway
{
    public function getShortName()
    {
        return 'Mollie';
    }

    /**
     * @param array $params
     * @return array $params
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
        return new DefaultTransformer();
    }
}
