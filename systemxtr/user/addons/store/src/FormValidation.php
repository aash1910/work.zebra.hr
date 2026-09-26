<?php

/*
 * Exp:resso Store module for ExpressionEngine
 * Copyright (c) 2010-2019 Exp:resso (support@exp-resso.com)
 */

namespace Store;

use EE_Form_validation;
use Store\Model\Status;

ee()->load->library('form_validation');

/**
 * We use our own custom form validation library, to work around some
 * inefficiencies of the built in EE validation library.
 *
 * This must be assigned to the EE global variable to work properly, for example:
 *
 *     ee()->form_validation = new \Store\FormValidation;
 */
class FormValidation extends EE_Form_validation
{
    public function error_array()
    {
        return $this->_error_array;
    }

    /**
     * Allow run to validate specific data, instead of directly accessing $_POST array
     *
     * @param string $group
     * @param array $data The data to validate
     * @return bool
     */
    public function run($group = '', $data = null)
    {
        if (null === $data) {
            return parent::run($group);
        }

        // unfortunately EE form validation class is hard coded to use $_POST
        $old_post = $_POST;
        $_POST = $data;
        $result = parent::run($group);
        $_POST = $old_post;

        return $result;
    }

    /**
     * Awesome function to manually add an error to the form
     *
     * @param $field
     * @param $message
     */
    public function add_error($field, $message)
    {
        // make sure we have data for this field
        if (empty($this->_field_data[$field])) {
            $this->set_rules($field, "lang:store.$field", '');
        }

        $this->_field_data[$field]['error'] = $message;
        $this->_error_array[$field] = $message;
    }

    /**
     * Add validation rules instead of overwriting them
     *
     * @param $field
     * @param string $label
     * @param string $rules
     */
    public function add_rules($field, $label = '', $rules = '')
    {
        // are there any existing rules for this field?
        if (!empty($this->_field_data[$field]['rules'])) {
            $rules = trim($this->_field_data[$field]['rules'] . '|' . $rules, '|');
        }

        $this->set_rules($field, $label, $rules);
    }

    public function add_rules_from_params($params)
    {
        // set error delimiters
        if (isset($params['error_delimiters'])) {
            $error_delimiters = explode('|', $params['error_delimiters']);
            if (count($error_delimiters) == 2) {
                $this->set_error_delimiters($error_delimiters[0], $error_delimiters[1]);
            }
        }

        foreach ($params as $key => $value) {
            if (strpos($key, 'rules:') !== 0) {
                continue;
            }

            $field = substr($key, 6);
            $this->add_rules($field, 'lang:store.' . $field, $value);
        }
    }

    public function store_currency_non_zero($str)
    {
        return store_round_currency(store_parse_decimal($str), true) != 0;
    }

    public function valid_payment_method($name)
    {
        if (empty($name)) {
            return true;
        }

        // check payment method exists in database
        $payment_method = ee()->store->payments->find_payment_method($name);

        return !empty($payment_method);
    }

    public function valid_shipping_method($shipping_index)
    {
        $shipping_index = (int)$shipping_index;
        $cart = ee()->store->orders->get_cart();
        $shipping_methods = ee()->store->shipping->get_order_shipping_methods($cart);

        $shipping_methods = is_array($shipping_methods) ? $shipping_methods : [];
        $method_ids = array_map(function ($method) {
            return $method->id;
        }, $shipping_methods);

        if (!in_array($shipping_index, $method_ids)) {
            $this->set_message('valid_shipping_method', lang('store.invalid_shipping_method'));

            return false;
        }

        return true;
    }

    public function valid_promo_code($promo_code)
    {
        $promo_code = (string)$promo_code;
        if ($promo_code == '') {
            return true;
        }

        $discounts = ee()->store->orders->get_available_discounts($promo_code, false);

        if (empty($discounts)) {
            $this->set_message('valid_promo_code', lang('store.promo_code_invalid'));

            return false;
        }

        return true;
    }

    public function require_accept_terms($str)
    {
        return !empty($str) and substr(strtolower($str), 0, 1) != 'n' and substr(strtolower($str), 0, 1) != '0';
    }

    public function unique_status_name($value, $current_id = 0)
    {
        if (empty($value)) {
            return true;
        }

        $count = Status::where('site_id', config_item('site_id'))
            ->where('id', '!=', $current_id)
            ->where('name', $value)
            ->count();

        return $count ? $count : true;
    }
}
