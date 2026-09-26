<?php

/*
 * Exp:resso Store module for ExpressionEngine
 * Copyright (c) 2010-2019 Exp:resso (support@exp-resso.com)
 */

namespace Store\Model;

class ShippingMethod extends AbstractModel
{
    protected $table = 'store_shipping_methods';
    protected $fillable = ['name', 'enabled'];

    public function rules()
    {
        return $this->hasMany('\Store\Model\ShippingRule');
    }
}
