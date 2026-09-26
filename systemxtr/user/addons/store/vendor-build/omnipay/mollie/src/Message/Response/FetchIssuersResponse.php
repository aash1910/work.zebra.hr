<?php

namespace Store\Dependency\Omnipay\Mollie\Message\Response;

use Store\Dependency\Omnipay\Common\Issuer;
use Store\Dependency\Omnipay\Common\Message\FetchIssuersResponseInterface;
/**
 * @see https://docs.mollie.com/reference/v2/methods-api/get-method
 */
class FetchIssuersResponse extends AbstractMollieResponse implements FetchIssuersResponseInterface
{
    /**
     * Return available issuers as an associative array.
     *
     * @return Issuer[]
     */
    public function getIssuers()
    {
        if (isset($this->data['issuers']) === \false) {
            return [];
        }
        $issuers = [];
        foreach ($this->data['issuers'] as $issuer) {
            $issuers[] = new Issuer($issuer['id'], $issuer['name'], $this->data['id']);
        }
        return $issuers;
    }
}
