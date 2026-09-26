<?php

/*
 * Exp:resso Store module for ExpressionEngine
 * Copyright (c) 2010-2019 Exp:resso (support@exp-resso.com)
 */

namespace Store\Model;

class Member extends AbstractModel
{
    protected $table = 'members';
    protected $primaryKey = 'member_id';

    public function data()
    {
        return $this->hasOne('\Store\Model\MemberData');
    }
}
