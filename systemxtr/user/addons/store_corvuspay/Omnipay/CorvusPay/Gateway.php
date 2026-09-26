<?php

namespace Omnipay\CorvusPay;

use Store\Dependency\Omnipay\Common\AbstractGateway;

class Gateway extends AbstractGateway
{
    public function getName()
    {
        return 'CorvusPay';
    }

    public function getDefaultParameters()
    {
        return array(
            'storeId'          => '',
            'secretKey'        => '',
            'language'         => 'hr',
            'require_complete' => false,
            'testMode'         => true,
            // Note: returnUrl / cancelUrl are supplied automatically by Store
            // and should not be configured as gateway settings.
        );
    }

    public function getStoreId()
    {
        return $this->getParameter('storeId');
    }

    public function setStoreId($value)
    {
        return $this->setParameter('storeId', $value);
    }

    public function getSecretKey()
    {
        return $this->getParameter('secretKey');
    }

    public function setSecretKey($value)
    {
        return $this->setParameter('secretKey', $value);
    }

    public function getLanguage()
    {
        return $this->getParameter('language');
    }

    public function setLanguage($value)
    {
        return $this->setParameter('language', $value);
    }

    public function getRequireComplete()
    {
        return (bool) $this->getParameter('require_complete');
    }

    public function setRequireComplete($value)
    {
        return $this->setParameter('require_complete', (bool) $value);
    }

    public function getTestMode()
    {
        return (bool) $this->getParameter('testMode');
    }

    public function setTestMode($value)
    {
        return $this->setParameter('testMode', (bool) $value);
    }

    /**
     * Initiate a purchase (off-site redirect via form POST).
     */
    public function purchase(array $parameters = array())
    {
        return $this->createRequest('\Omnipay\CorvusPay\Message\PurchaseRequest', $parameters);
    }

    /**
     * Complete a purchase after the buyer returns from CorvusPay.
     */
    public function completePurchase(array $parameters = array())
    {
        return $this->createRequest('\Omnipay\CorvusPay\Message\CompletePurchaseRequest', $parameters);
    }
}
