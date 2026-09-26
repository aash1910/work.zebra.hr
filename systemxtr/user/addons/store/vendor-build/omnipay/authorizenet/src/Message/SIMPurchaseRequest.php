<?php

namespace Store\Dependency\Omnipay\AuthorizeNet\Message;

/**
 * Authorize.Net SIM Purchase Request
 */
class SIMPurchaseRequest extends SIMAuthorizeRequest
{
    protected $action = 'AUTH_CAPTURE';
}
