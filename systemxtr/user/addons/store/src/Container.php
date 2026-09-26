<?php

/*
 * Exp:resso Store module for ExpressionEngine
 * Copyright (c) 2010-2019 Exp:resso (support@exp-resso.com)
 */

namespace Store;

use Store\Dependency\Illuminate\CodeIgniter\CodeIgniterConnectionResolver;
use Store\Dependency\Illuminate\Support\Str;
use Store\Model\AbstractModel;

/**
 * Store service container
 */
#[\AllowDynamicProperties]
class Container
{
    public $composer;
    protected $ee;

    public function __construct($ee)
    {
        $this->ee = ee();
    }

    public function initialize()
    {
        // pass all db queries to CI
        $resolver = new CodeIgniterConnectionResolver($this->ee);
        AbstractModel::setConnectionResolver($resolver);
        $this->db = $resolver->connection();

        // load store config
        $this->config->load();
    }

    public function __get($name)
    {
        $class = 'Store\\Service\\' . Str::studly($name) . 'Service';
        $this->$name = new $class($this->ee);

        return $this->$name;
    }

    public function set_composer($getComposer)
    {
        $this->composer = $getComposer;
    }

    public function get_composer()
    {
        return $this->composer;
    }
}
