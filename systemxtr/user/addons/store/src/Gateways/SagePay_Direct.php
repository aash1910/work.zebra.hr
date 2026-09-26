<?php

/*
 * Exp:resso Store module for ExpressionEngine
 * Copyright (c) 2010-2019 Exp:resso (support@exp-resso.com)
 */

namespace Store\Gateways;

use Store\Dependency\Omnipay\Common\Message\RequestInterface;
use Store\Dependency\Omnipay\SagePay\DirectGateway;
use Store\Transformer\AbstractTransformer;
use Store\Transformer\SagePayTransformer;

/**
 * This is a SagePay_Direct override class that will allow it to work more seamlessly with Exp:resso Store
 * @method RequestInterface updateCard(array $options = array())
 */
class SagePay_Direct extends DirectGateway
{
    public function getShortName()
    {
        return 'SagePay_Direct';
    }

    /**
     * @return AbstractTransformer
     */
    public function getTransformer()
    {
        return new SagePayTransformer();
    }
}
