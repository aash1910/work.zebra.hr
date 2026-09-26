<?php

/*
 * Exp:resso Store module for ExpressionEngine
 * Copyright (c) 2010-2019 Exp:resso (support@exp-resso.com)
 */

namespace Store\Gateways;

use Store\Dependency\Omnipay\Common\Message\RequestInterface;
use Store\Dependency\Omnipay\Manual\Gateway;
use Store\Transformer\AbstractTransformer;
use Store\Transformer\DefaultTransformer;

/**
 * This is a Manual override class that will allow it to work more seamlessly with Exp:resso Store
 * @method RequestInterface completeAuthorize(array $options = array())
 * @method RequestInterface createCard(array $options = array())
 * @method RequestInterface updateCard(array $options = array())
 * @method RequestInterface deleteCard(array $options = array())
 */
class Manual extends Gateway
{
    public function getShortName()
    {
        return 'Manual';
    }

    /**
     * @return AbstractTransformer
     */
    public function getTransformer()
    {
        return new DefaultTransformer();
    }
}
