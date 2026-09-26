<?php

/**
 * Manual Gateway Response
 */
namespace Store\Dependency\Omnipay\Manual\Message;

use Store\Dependency\Omnipay\Common\Message\AbstractResponse;
/**
 * Manual Gateway Response
 */
class Response extends AbstractResponse
{
    public function isSuccessful()
    {
        return \true;
    }
}
