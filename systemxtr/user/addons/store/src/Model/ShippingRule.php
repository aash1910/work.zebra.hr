<?php

/*
 * Exp:resso Store module for ExpressionEngine
 * Copyright (c) 2010-2019 Exp:resso (support@exp-resso.com)
 */

namespace Store\Model;

class ShippingRule extends AbstractModel
{
    protected $table = 'store_shipping_rules';
    protected $fillable = [
        'name', 'country_code', 'state_code', 'postcode', 'min_weight', 'max_weight', 'min_order_total',
        'max_order_total', 'min_order_qty', 'max_order_qty', 'base_rate', 'per_item_rate', 'per_weight_rate',
        'percent_rate', 'min_rate', 'max_rate', 'enabled',
    ];
    protected $decimal_attributes = [
        'min_weight', 'max_weight', 'min_order_total', 'max_order_total', 'min_order_qty', 'max_order_qty', 'base_rate',
        'per_item_rate', 'per_weight_rate', 'percent_rate', 'min_rate', 'max_rate',
    ];

    public function getOrderQtyTextAttribute()
    {
        if ($this->min_order_qty || $this->max_order_qty) {
            $text = (int)$this->min_order_qty;
            $text .= $this->max_order_qty ? ' - ' . $this->max_order_qty : '+';

            return $text;
        }
    }

    public function getOrderTotalTextAttribute()
    {
        if ($this->min_order_total || $this->max_order_total) {
            // cast to string forces display of lower bound
            $text = store_currency((string)$this->min_order_total);
            $text .= $this->max_order_total ? ' - ' . store_currency($this->max_order_total) : '+';

            return $text;
        }
    }

    public function getWeightTextAttribute()
    {
        if ($this->min_weight || $this->max_weight) {
            $text = (float)$this->min_weight;
            $text .= $this->max_weight ? ' - ' . $this->max_weight : '+';

            return $text;
        }
    }

    public function getRateTextAttribute()
    {
        // describe rate in an easy to read manner
        $text = '';
        if ((float)$this->base_rate) {
            $text .= store_currency($this->base_rate) . ' + ';
        }
        if ((float)$this->per_item_rate) {
            $text .= store_currency($this->per_item_rate) . ' ' . lang('store.shipping_rule_per_item') . ' + ';
        }
        if ((float)$this->per_weight_rate) {
            $text .= store_currency($this->per_weight_rate) . ' ' . lang('store.shipping_rule_per_weight_unit') . ' + ';
        }
        if ((float)$this->percent_rate) {
            $text .= $this->percent_rate . '% ' . lang('store.shipping_of_order_total');
        }
        $text = trim($text, ' +');
        if ((float)$this->min_rate) {
            $text .= ', ' . sprintf(lang('store.shipping_with_a_min_of'), store_currency($this->min_rate));
        }
        if ((float)$this->max_rate) {
            $text .= ', ' . sprintf(lang('store.shipping_up_to_a_max_of'), store_currency($this->max_rate));
        }
        $text = trim($text, ' ,');

        return $text;
    }
}
