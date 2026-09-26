<?php

namespace Store\Dependency\Illuminate\Support;

use Store\Dependency\Carbon\Carbon as BaseCarbon;
use Store\Dependency\Carbon\CarbonImmutable as BaseCarbonImmutable;
use Store\Dependency\Illuminate\Support\Traits\Conditionable;
class Carbon extends BaseCarbon
{
    use Conditionable;
    /**
     * {@inheritdoc}
     */
    public static function setTestNow($testNow = null)
    {
        BaseCarbon::setTestNow($testNow);
        BaseCarbonImmutable::setTestNow($testNow);
    }
}
