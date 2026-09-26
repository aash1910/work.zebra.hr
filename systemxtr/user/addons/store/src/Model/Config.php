<?php

/*
 * Exp:resso Store module for ExpressionEngine
 * Copyright (c) 2010-2019 Exp:resso (support@exp-resso.com)
 */

namespace Store\Model;

class Config extends AbstractModel
{
    protected $table = 'store_config';

    protected $fillable = ['site_id', 'preference'];

    public function getValueAttribute($value)
    {
        return json_decode($value, true);
    }

    /**
     * Settings are stored in database as JSON
     *
     * @param $value
     */
    public function setValueAttribute($value)
    {
        $this->attributes['value'] = json_encode($value);
    }
}
