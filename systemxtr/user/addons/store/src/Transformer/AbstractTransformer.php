<?php

/*
 * Exp:resso Store module for ExpressionEngine
 * Copyright (c) 2010-2019 Exp:resso (support@exp-resso.com)
 */

namespace Store\Transformer;

use Store\Model\Order;
use Store\Model\Transaction;

/**
 * This is an abstract data transformer class for the request data being sent to payment gateways
 */
abstract class AbstractTransformer
{
    /**
     * @return array $request
     */
    abstract public function transform(Transaction $transaction);

    /**
     * @return array $request
     */
    abstract public function cardTransform(Order $order);
}
