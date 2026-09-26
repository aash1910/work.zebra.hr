<?php

/*
 * Exp:resso Store module for ExpressionEngine
 * Copyright (c) 2010-2019 Exp:resso (support@exp-resso.com)
 */

namespace Store\Model;

class State extends AbstractModel
{
    protected $table = 'store_states';
    protected $fillable = ['name', 'code', 'delete'];

    /**
     * Fake attribute to store whether state should be deleted
     */
    protected $_delete;

    public function getDeleteAttribute()
    {
        return $this->_delete;
    }

    public function setDeleteAttribute($value)
    {
        $this->_delete = $value;
    }
}
