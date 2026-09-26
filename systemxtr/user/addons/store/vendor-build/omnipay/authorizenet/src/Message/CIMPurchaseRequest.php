<?php

namespace Store\Dependency\Omnipay\AuthorizeNet\Message;

/**
 * Creates a Authorize capture transaction request for the specified card
 */
class CIMPurchaseRequest extends CIMAuthorizeRequest
{
    protected $action = "authCaptureTransaction";
}
