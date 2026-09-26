<?php

/*
 * Exp:resso Store module for ExpressionEngine
 * Copyright (c) 2010-2019 Exp:resso (support@exp-resso.com)
 */

namespace Store;

use Store\Dependency\Carbon\Carbon;

class DateTime extends Carbon
{
    /**
     * Format using EE's special syntax and localization
     *
     * @param $format
     * @return string
     */
    public function formatEE($format)
    {
        return ee()->localize->format_date($format, $this->timestamp, $this->timezoneName);
    }
}
