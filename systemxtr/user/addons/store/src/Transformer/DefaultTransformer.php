<?php

/*
 * Exp:resso Store module for ExpressionEngine
 * Copyright (c) 2010-2019 Exp:resso (support@exp-resso.com)
 */

namespace Store\Transformer;

use Store\Model\Order;
use Store\Model\Transaction;

/**
 * This is a Default data transformer that will set up the request data for each gateway
 */
class DefaultTransformer extends AbstractTransformer
{
    /**
     * @return array $request
     */
    public function transform(Transaction $transaction)
    {
        return [];
    }

    /**
     * @return array $request
     */
    public function cardTransform(Order $order)
    {
        return [];
    }
}
