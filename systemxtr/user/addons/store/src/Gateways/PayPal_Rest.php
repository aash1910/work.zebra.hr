<?php

/*
 * Exp:resso Store module for ExpressionEngine
 * Copyright (c) 2010-2019 Exp:resso (support@exp-resso.com)
 */

namespace Store\Gateways;

use Store\Dependency\Omnipay\Common\Message\RequestInterface;
use Store\Dependency\Omnipay\PayPal\RestGateway;
use Store\Transformer\AbstractTransformer;
use Store\Transformer\PayPalTransformer;

/**
 * This is a PayPal_Rest override class that will allow it to work more seamlessly with Exp:resso Store
 * @method RequestInterface completeAuthorize(array $options = array())
 * @method RequestInterface updateCard(array $options = array())
 */
class PayPal_Rest extends RestGateway
{
    public function getShortName()
    {
        return 'PayPal_Rest';
    }

    /**
     * @return AbstractTransformer
     */
    public function getTransformer()
    {
        return new PayPalTransformer();
    }
}
