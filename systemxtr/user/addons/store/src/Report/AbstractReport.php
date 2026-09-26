<?php

/*
 * Exp:resso Store module for ExpressionEngine
 * Copyright (c) 2010-2019 Exp:resso (support@exp-resso.com)
 */

namespace Store\Report;

use Store\OptionBag;
use Store\Dependency\Illuminate\Support\Arr;
abstract class AbstractReport
{
    protected $ee;
    protected $options;
    protected $orderby;
    protected $sort = 'asc';
    protected $render;

    /**
     * Create a new report
     *
     * @param mixed $ee A reference to the global EE object
     * @param AbstractRenderer $renderer A renderer to format the report
     * @param array|null $options An array of options to initialize the report with
     */
    public function __construct($ee, AbstractRenderer $renderer, $options = [])
    {
        $this->ee = $ee;
        $this->render = $renderer;
        $this->orderby = Arr::pull($options, 'orderby') ?: $this->orderby;
        $this->sort = Arr::pull($options, 'sort') ?: $this->sort;
        $this->options = new OptionBag($this->default_options(), $options);
    }

    abstract public function default_options();

    public function options()
    {
        return $this->options;
    }
}
