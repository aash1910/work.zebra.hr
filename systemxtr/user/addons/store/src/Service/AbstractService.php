<?php

/*
 * Exp:resso Store module for ExpressionEngine
 * Copyright (c) 2010-2019 Exp:resso (support@exp-resso.com)
 */

namespace Store\Service;

abstract class AbstractService
{
    protected $ee;

    public function __construct($ee)
    {
        $this->ee = $ee;
    }
}
