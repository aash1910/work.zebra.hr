<?php

/*
 * Exp:resso Store module for ExpressionEngine
 * Copyright (c) 2010-2019 Exp:resso (support@exp-resso.com)
 */

namespace Store;

use ArrayAccess;
use Store\Dependency\Illuminate\Support\Arr;

class OptionBag implements ArrayAccess
{
    protected $options;
    protected $values;

    /**
     * Create a new OptionBag
     *
     * @param array $options An array of configuration options
     * @param array|null $values An array of initial values
     */
    public function __construct(array $options, $values = [])
    {
        $this->configure($options);
        $this->replace($values);
    }

    protected function configure(array $options)
    {
        $this->options = [];

        foreach ($options as $key => $option) {
            if (!is_array($option)) {
                $option = ['type' => 'text', 'default' => $option];
            }

            $this->configure_option($key, $option);
        }
    }

    protected function configure_option($key, array $option)
    {
        // default parameters
        $option = array_merge(['default' => null], $option);

        if ($option['type'] === 'month_select') {
            // automatic options list
            $option['options'] = ee()->store->reports->month_select_options();

            // default value may be specified as integer offset
            if (is_int($option['default']) && $option['default'] < 0) {
                $option_keys = array_keys($option['options']);
                $default_offset = max(0, count($option_keys) + $option['default']);
                $option['default'] = $option_keys[$default_offset];
            }
        } elseif ($option['type'] === 'category_select') {
            // automatic categories list
            $option['options'] = ['' => lang('store.any')];
            $option['options'] += ee()->store->products->get_categories();
        }

        $this->options[$key] = $option;
    }

    /**
     * Get all option values as an associative array
     */
    public function all()
    {
        return $this->values;
    }

    /**
     * Get all option keys
     */
    public function keys()
    {
        return array_keys($this->options);
    }

    /**
     * Replace all options
     *
     * @param array|null $values An array of values
     */
    public function replace($values)
    {
        // initialize default values
        $this->values = [];
        foreach ($this->options as $key => $value) {
            $this->set($key, $this->def($key));
        }

        // set new values
        foreach ((array)$values as $key => $value) {
            $this->set($key, $value);
        }
    }

    /**
     * Get an option
     *
     * @param $key
     * @return mixed
     */
    public function get($key)
    {
        // intentionally using $this->values here in case the value hasn't been set yet
        if (isset($this->values[$key])) {
            $value = $this->values[$key];

            // date_select automatically returns DateTime
            if ($value && $this->options[$key]['type'] === 'date_select') {
                return DateTime::createFromFormat('Y-m-d', $value, ee()->store->reports->timezone())->startOfDay();
            }

            return $value;
        }
    }

    /**
     * Set an option
     *
     * @param $key
     * @param $value
     */
    public function set($key, $value)
    {
        if ($this->has($key)) {
            $this->values[$key] = $value;
        }
    }

    /**
     * Detect whether the bag is allowed to contain a specific option
     *
     * @param $key
     * @return bool
     */
    public function has($key)
    {
        return isset($this->options[$key]);
    }

    /**
     * Fetch the default value for an option (stupid PHP reserved keywords).
     *
     * @param $key
     * @return mixed|null
     */
    public function def($key)
    {
        if (isset($this->options[$key])) {
            return $this->options[$key]['default'];
        }
    }

    public function offsetExists($key): bool
    {
        return $this->has($key);
    }

    public function offsetGet($key): mixed
    {
        return $this->get($key);
    }

    public function offsetSet($key, $value): void
    {
        $this->set($key, $value);
    }

    public function offsetUnset($key): void
    {
        $this->set($key, null);
    }

    public function input($key)
    {
        $attributes = $this->options[$key];
        unset($attributes['default']);

        $attributes['name'] = "options[$key]";
        $attributes['id'] = "options_$key";
        $value = $this->get($key);

        switch ($attributes['type']) {
            case 'textarea':
                return store_html_elem('textarea', $attributes, $value);
            case 'select':
            case 'month_select':
            case 'category_select':
                $content = store_select_options($attributes['options'], $value);
                unset($attributes['options']);

                return store_html_elem('select', $attributes, $content, true);
            case 'date_select':
                $attributes['class'] = Arr::get($attributes, 'class') . ' store_date';
                $attributes['type'] = 'text';
                $attributes['value'] = $value ? $value->format('Y-m-d') : null;

                return store_html_elem('input', $attributes);
            default:
                $attributes['value'] = $value;

                return store_html_elem('input', $attributes);
        }
    }
}
