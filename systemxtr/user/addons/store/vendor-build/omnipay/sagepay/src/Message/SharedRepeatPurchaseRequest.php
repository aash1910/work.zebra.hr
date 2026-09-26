<?php

namespace Store\Dependency\Omnipay\SagePay\Message;

use Store\Dependency\Omnipay\Common\Helper;
/**
 * Sage Pay Direct Repeat Authorize Request
 */
class SharedRepeatPurchaseRequest extends SharedRepeatAuthorizeRequest
{
    public function getTxType()
    {
        return static::TXTYPE_REPEAT;
    }
}
