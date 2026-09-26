<?php

/*
 * Exp:resso Store module for ExpressionEngine
 * Copyright (c) 2010-2019 Exp:resso (support@exp-resso.com)
 */

namespace Store\Gateways;

use Store\Dependency\Omnipay\Dummy\Gateway;
use Store\Transformer\AbstractTransformer;
use Store\Transformer\DefaultTransformer;

/**
 * This is a Dummy override class that will allow it to work more seamlessly with Exp:resso Store
 */
class Dummy extends Gateway
{
    public function getShortName()
    {
        return 'Dummy';
    }

    /**
     * @return AbstractTransformer
     */
    public function getTransformer()
    {
        return new DefaultTransformer();
    }

    /**
     * Return a descriptive error message for Dummy gateway failures
     */
    public function getFailureMessage($request, $response)
    {
        $cardNumber = null;
        if (method_exists($request, 'getCard') && $request->getCard()) {
            $cardNumber = $request->getCard()->getNumber();
        }
        if ($cardNumber && is_numeric($cardNumber)) {
            if (substr($cardNumber, -1, 1) % 2 !== 0) {
                return 'Card declined: card number ends in an odd digit (Dummy gateway test rule).';
            } else {
                return 'Card declined for unknown Dummy gateway reason.';
            }
        } else {
            return 'Card declined: invalid or missing card number (Dummy gateway).';
        }
    }
}
