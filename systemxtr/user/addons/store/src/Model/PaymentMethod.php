<?php

/*
 * Exp:resso Store module for ExpressionEngine
 * Copyright (c) 2010-2019 Exp:resso (support@exp-resso.com)
 */

namespace Store\Model;

use Store\Dependency\Omnipay\Common\GatewayInterface;
use Store\Dependency\Omnipay\Omnipay;

class PaymentMethod extends AbstractModel
{
    protected $table = 'store_payment_methods';

    /**
     * @param null $httpClient
     * @return GatewayInterface
     */
    public function createGateway($httpClient = null)
    {
        $gateway = Omnipay::create(ee()->store->payments->getGatewayClassName($this->class), $httpClient);
        $gateway->initialize($this->settings);

        return $gateway;
    }

    public function getSettingsAttribute()
    {
        $settings = json_decode((string) $this->attributes['settings'], true);

        return is_array($settings) ? $settings : [];
    }

    public function setSettingsAttribute($value)
    {
        $this->attributes['settings'] = empty($value) ? null : json_encode((array)$value);
    }
}
