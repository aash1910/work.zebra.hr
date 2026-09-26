<?php

/*
 * Exp:resso Store module for ExpressionEngine
 * Copyright (c) 2010-2019 Exp:resso (support@exp-resso.com)
 */

namespace Store;
use Store\Dependency\Illuminate\Support\Str;

class FormBuilder
{
    public $model;
    public $prefix;

    public function __construct($model = null, $prefix = null)
    {
        $this->model = $model;
        $this->prefix = $prefix;

        if ($model) {
            $class_parts = explode('\\', get_class($model));
            $this->prefix = $prefix ?: Str::snake(end($class_parts));
        }
    }

    /**
     * Create opening form tag
     *
     * @return string
     */
    public function open(array $options = [])
    {
        $default_action = isset($options['action']) ? $options['action'] : ee()->store->request->getRequestUri();
        $options = array_merge([
            'action' => $default_action,
            'method' => 'post',
        ], $options);

        $out = $this->elem('form', $options, null, false);
        $out .= "\n<div style='margin:0;padding:0;display:inline;'>";
        $out .= $this->elem('input', [
            'type'  => 'hidden',
            'name'  => 'csrf_token',
            'value' => CSRF_TOKEN,
        ]);
        $out .= '</div>';

        return $out;
    }

    /**
     * Create closing form tag
     */
    public function close()
    {
        return '</form>';
    }

    /**
     * Create label
     *
     * @param $property
     * @param null $name
     * @return string
     */
    public function label($property, $name = null, array $options = [])
    {
        $options = array_merge([
            'unprefixed' => false,
        ], $options);

        // Set the 'for' attribute based on whether it should be prefixed
        if ($name !== null) {
            $options['for'] = $name;
        } else {
            $options['for'] = $options['unprefixed'] ? $property : $this->prefix . '_' . $property;
        }

        $subtext = null;

        if (null === $name) {
            // Try the prefixed key first
            $prefixed_key = 'store.' . $options['for'];
            if (!$options['unprefixed'] && ee()->lang->line($prefixed_key) !== false) {
                $name = $prefixed_key;
            }
            // Then try with model prefix (e.g. tax_property)
            else if (!$options['unprefixed'] && ee()->lang->line('store.' . $this->prefix . '_' . $property) !== false) {
                $name = 'store.' . $this->prefix . '_' . $property;
            }
            // Then try with the unprefixed input name
            else if (ee()->lang->line('store.' . $property) !== false) {
                $name = 'store.' . $property;
            }
            // If no language key found, use the property name
            else {
                $name = $property;
            }
        }

        // Check for subtext using the same key pattern as the main label
        $subtext_key = $name . '_subtext';
        if (ee()->lang->line($subtext_key) === false) {
            $subtext_key = 'store.' . $options['for'] . '_subtext';
        }

        // Try with model prefix if the above fails
        if (ee()->lang->line($subtext_key) === false && !$options['unprefixed']) {
            $subtext_key = 'store.' . $this->prefix . '_' . $property . '_subtext';
        }
        if (ee()->lang->line($subtext_key) !== false && ee()->lang->line($subtext_key) !== $subtext_key) {
            $subtext = '<span class="label-subtext">' . $this->e(ee()->lang->line($subtext_key)) . '</span>';
        }

        $content = $this->e(ee()->lang->line($name) !== false ? ee()->lang->line($name) : $name);

        if (!empty($options['required'])) {
            $content .= ' <em class="required">*</em>';
            unset($options['required']);
        }

        // Remove unprefixed from options before creating element
        unset($options['unprefixed']);

        return $this->elem('label', $options, $content) . ($subtext ? "\n" . $subtext : '');
    }

    /**
     * Create text input
     *
     * @param $property
     * @return string
     */
    public function input($property, array $options = [])
    {
        $options = array_merge([
            'type'  => 'text',
            'id'    => $this->id($property),
            'name'  => $this->name($property),
            'value' => $this->value($property),
        ], $options);

        return $this->elem('input', $options);
    }

    public function hidden($property, array $options = [])
    {
        $options['type'] = 'hidden';

        return $this->input($property, $options);
    }

    public function currency($property, array $options = [])
    {
        $options['value'] = store_currency_cp($this->value($property));

        return $this->input($property, $options);
    }

    public function decimal($property, array $options = [])
    {
        $options['value'] = store_decimal($this->value($property));

        return $this->input($property, $options);
    }

    public function percent($property, array $options = [])
    {
        $value = (float)$this->value($property);
        $options['value'] = $value ? $value . '%' : null;

        return $this->input($property, $options);
    }

    public function datetime($property, array $options = [])
    {
        $options['value'] = ee()->localize->human_time($this->value($property));
        $options['class'] = (isset($options['class']) ? $options['class'] : '') . 'store_datetime';

        return $this->input($property, $options);
    }

    /**
     * Create textarea
     *
     * @param $property
     * @return string
     */
    public function text($property, array $options = [])
    {
        $options = array_merge([
            'id'   => $this->id($property),
            'name' => $this->name($property),
        ], $options);

        return $this->elem('textarea', $options, $this->e($this->value($property)));
    }

    /**
     * Create checkbox
     *
     * @param $property
     * @return string
     */
    public function checkbox($property, array $options = [])
    {
        $hidden = [];
        $hidden['type'] = 'hidden';
        $hidden['name'] = $this->name($property);
        $hidden['value'] = 0;

        $options = array_merge([
            'type'    => 'checkbox',
            'id'      => $this->id($property),
            'name'    => $this->name($property),
            'value'   => 1,
            'checked' => (bool)$this->value($property),
        ], $options);

        return $this->elem('input', $hidden) . $this->elem('input', $options);
    }

    /**
     * Create select menu
     *
     * @param $property
     * @param $items
     * @return string
     */
    public function select($property, $items, array $options = [])
    {
        $options = array_merge([
            'id'   => $this->id($property),
            'name' => $this->name($property),
        ], $options);

        $html = '';

        if (!empty($options['multiple'])) {
            // add default select multiple size
            $options['name'] .= '[]';
            $options = array_merge([
                'size'  => 6,
                'class' => 'store-multiselect',
            ], $options);

            // add blank hidden input to support selecting no items
            $html .= $this->elem('input', [
                'type'  => 'hidden',
                'name'  => $options['name'],
                'value' => '',
            ]);
        }

        if (isset($options['selected'])) {
            $selected = $options['selected'];
            unset($options['selected']);
        } else {
            $selected = $this->value($property);
        }

        if (is_array($items)) {
            $content = '';
            foreach ($items as $key => $value) {
                $opt = [];
                $opt['value'] = $key;
                if ($selected == $key) {
                    $opt['selected'] = true;
                }
                if (is_array($selected) && (in_array($key, $selected) || (empty($selected) && $key == ''))) {
                    $opt['selected'] = true;
                }

                $content .= $this->elem('option', $opt, $this->e($value)) . "\n";
            }
        } else {
            $content = $items;
        }

        return $html . $this->elem('select', $options, $content);
    }

    /**
     * Get property error message from CI form validation library
     *
     * @param $property
     */
    public function error($property)
    {
    }

    /**
     * Create a generic HTML element
     *
     * @param $name
     * @param $options
     * @param null $content
     * @param bool $close
     * @return string
     */
    public function elem($name, $options, $content = null, $close = true)
    {
        $html = '<' . $name;
        foreach ($options as $key => $value) {
            if (is_bool($value)) {
                if ($value) {
                    $html .= ' ' . $this->e($key);
                }
            } else {
                $html .= ' ' . $this->e($key) . '="' . $this->e($value) . '"';
            }
        }

        if (!$close) {
            // content will normally be null for open tag
            $html .= '>' . $content;
        } elseif (null === $content) {
            $html .= ' />';
        } else {
            // content must already be escaped
            $html .= '>' . $content . '</' . $name . '>';
        }

        return $html;
    }

    public function id($property)
    {
        return $this->prefix . '_' . $property;
    }

    public function name($property)
    {
        return $this->prefix . '[' . $property . ']';
    }

    public function value($property)
    {
        if ($this->model) {
            return $this->model->$property;
        }
    }

    /**
     * Escape HTML
     *
     * @param $value
     * @return string
     */
    public function e($value)
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, null, false);
    }
}
