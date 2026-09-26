<?php

/*
 * Exp:resso Store module for ExpressionEngine
 * Copyright (c) 2010-2019 Exp:resso (support@exp-resso.com)
 */

namespace Store\Update;

use Store\Update;

class Update122
{
    /**
     * Add new logout hook
     */
    public function up()
    {
        Update::register_hook('member_member_logout');
    }
}
