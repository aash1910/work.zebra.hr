<?php

/*
 * Exp:resso Store module for ExpressionEngine
 * Copyright (c) 2010-2019 Exp:resso (support@exp-resso.com)
 */

namespace Store\Transformer;

use Store\Model\Order;
use Store\Model\Transaction;

/**
 * This is a PayPal data transformer that will add in necessary request data that PayPal expects
 */
class PayPalTransformer extends AbstractTransformer
{
    /**
     * @return array $request
     */
    public function transform(Transaction $transaction)
    {
        return [
            'noShipping'      => 1,
            'allowNote'       => 0,
            'addressOverride' => 1,
        ];
    }

    /**
     * @return array $request
     */
    public function cardTransform(Order $order)
    {
        return [];
    }
}
