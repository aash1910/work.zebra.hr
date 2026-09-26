<?php

/*
 * Exp:resso Store module for ExpressionEngine
 * Copyright (c) 2010-2019 Exp:resso (support@exp-resso.com)
 */

namespace Store\Service;

use Store\Model\Config;

class ConfigService extends AbstractService
{
    public $settings = [
        'store_cart_expiry'                 => 1440,
        'store_cc_payment_method'           => ['type' => 'select', 'options' => ['purchase' => 'store.settings.cc_payment_method_purchase', 'authorize' => 'store.settings.cc_payment_method_authorize'], 'default' => 'purchase'],
        'store_conversion_tracking_extra'   => ['type' => 'textarea', 'default' => ''],
        'store_currency_code'               => 'USD',
        'store_currency_dec_point'          => '.',
        'store_currency_decimals'           => 2,
        'store_currency_suffix'             => '',
        'store_currency_symbol'             => '$',
        'store_currency_thousands_sep'      => ',',
        'store_default_country'             => '',
        'store_default_order_address'       => ['type' => 'select', 'options' => ['none' => 'store.none', 'shipping_same_as_billing' => 'store.shipping_same_as_billing', 'billing_same_as_shipping' => 'store.billing_same_as_shipping'], 'default' => 'none'],
        'store_default_shipping_method_id'  => '',
        'store_default_state'               => '',
        'store_dimension_units'             => ['type' => 'select', 'options' => ['mm' => 'store.settings.dimension_units_mm', 'cm' => 'store.settings.dimension_units_cm', 'm' => 'store.settings.dimension_units_m', 'ft' => 'store.settings.dimension_units_ft', 'in' => 'store.settings.dimension_units_in'], 'default' => 'mm'],
        'store_unofficial_payment_gateways' => ['type' => 'select', 'options' => ['1' => 'yes', '0' => 'no'], 'default' => '0'],
        'store_force_member_login'          => ['type' => 'select', 'options' => ['1' => 'yes', '0' => 'no'], 'default' => '0'],
        'store_from_email'                  => '',
        'store_from_name'                   => '',
        'store_google_analytics_ecommerce'  => ['type' => 'select', 'options' => ['1' => 'store.enabled', '0' => 'store.disabled'], 'default' => '1'],
        'store_order_fields'                => '',
        'store_order_id_start'              => '1',
        'store_order_invoice_url'           => '',
        'store_secure_template_tags'        => ['type' => 'select', 'options' => ['1' => 'yes', '0' => 'no'], 'default' => '0'],
        'store_security'                    => '',
        'store_reporting_timezone'          => ['type' => 'timezone'],
        'store_weight_units'                => ['type' => 'select', 'options' => ['g' => 'store.settings.weight_units_g', 'kg' => 'store.settings.weight_units_kg', 'lb' => 'store.settings.weight_units_lb'], 'default' => 'g'],
    ];

    protected $cached_order_fields;

    public function items()
    {
        $items = ['store_site_enabled' => false];

        if (!$this->ee->db->table_exists('store_config')) {
            return $items;
        }

        $query = $this->ee->db->from('store_config')
            ->where('site_id', \config_item('site_id'))
            ->get();
        $configs = $query->result();

        if (empty($configs)) {
            return $items;
        }

        foreach ($this->settings as $key => $default) {
            $items[$key] = store_setting_default($default);
        }

        foreach ($configs as $row) {
            $key = $row->preference;
            if (isset($this->settings[$key])) {
                $value = $row->value;

                // Clean up value - remove quotes if they're wrapping the entire string
                if (is_string($value) && preg_match('/^".*"$/', $value)) {
                    $value = json_decode($value);
                }

                $items[$key] = $value;
            }
        }

        $items['store_site_enabled'] = true;

        return $items;
    }

    public function load()
    {
        $items = $this->items();
        foreach ($items as $key => $value) {
            $this->ee->config->set_item($key, $value);
        }
    }

    public function update($items)
    {
        $currentSettings = $this->items();

        foreach ($currentSettings as $key => $value) {
            // do we have a new value for this preference?
            if (isset($items[$key])) {
                $value = $items[$key];

                // Ensure values aren't double-encoded with quotes
                if (is_string($value) && preg_match('/^".*"$/', $value)) {
                    $value = json_decode($value);
                }

                $this->ee->config->set_item($key, $value);
            }

            // Get existing config record or create a new one
            $config = Config::firstOrNew([
                'site_id' => config_item('site_id'),
                'preference' => $key
            ]);

            // Set the value using the proper ORM method
            // Prevent double-quoting of string values
            if (is_string($value) && !is_numeric($value) && $value !== '' &&
                strpos($key, '_dec_point') === false && strpos($key, '_thousands_sep') === false) {
                // Ensure we're not storing strings with extra quotes
                $value = trim($value, '"');
            }

            $config->value = $value;
            $config->save();
        }

        // Clear cached order fields if we're updating order fields
        if (isset($items['store_order_fields'])) {
            $this->clear_order_fields_cache();
        }
    }

    /**
     * Clear the cached order fields to force reload
     */
    public function clear_order_fields_cache()
    {
        $this->cached_order_fields = null;
    }

    /**
     * Lazy load all order fields for current site
     */
    public function order_fields()
    {
        if (is_null($this->cached_order_fields)) {
            $this->cached_order_fields = $this->order_field_defaults();

            // load data from current site config
            $config = config_item('store_order_fields');

            // Handle case where config might be a JSON string
            if (is_string($config)) {
                $decoded = json_decode($config, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $config = $decoded;
                }
            }

            // avoid PHP isset() bug #53971 on < 5.3.6
            if (is_array($config)) {
                foreach ($this->cached_order_fields as $key => $field) {

                    // does field have a custom name?
                    if (isset($field['title']) and isset($config[$key]['title'])) {
                        $this->cached_order_fields[$key]['title'] = $config[$key]['title'];
                        if ($key === 'order_custom9' && function_exists('log_message')) {
                            log_message('debug', 'Updated order_custom9 title to: ' . $config[$key]['title']);
                        }
                    }

                    // is field mapped to a member field?
                    if (isset($config[$key]['member_field'])) {
                        $this->cached_order_fields[$key]['member_field'] = $config[$key]['member_field'];
                    }
                }
            } else {
                if (function_exists('log_message')) {
                    log_message('debug', 'Store order_fields config is not an array!');
                }
            }

            // Debug: Log the final result
            if (function_exists('log_message')) {
                log_message('debug', 'Store order_fields final result for order_custom9: ' . print_r($this->cached_order_fields['order_custom9'], true));
            }
        }

        return $this->cached_order_fields;
    }

    public function order_field_defaults()
    {
        return [
            'billing_first_name'  => ['title' => '', 'member_field' => 'first_name'],
            'billing_last_name'   => ['title' => '', 'member_field' => 'last_name'],
            'billing_address1'    => ['title' => '', 'member_field' => 'address'],
            'billing_address2'    => ['title' => '', 'member_field' => 'address2'],
            'billing_city'        => ['title' => '', 'member_field' => 'city'],
            'billing_state'       => ['title' => '', 'member_field' => 'state'],
            'billing_country'     => ['title' => '', 'member_field' => 'country'],
            'billing_postcode'    => ['title' => '', 'member_field' => 'zip'],
            'billing_phone'       => ['title' => '', 'member_field' => 'phone'],
            'billing_company'     => ['title' => '', 'member_field' => 'company'],
            'shipping_first_name' => ['title' => '', 'member_field' => 'shipping_first_name'],
            'shipping_last_name'  => ['title' => '', 'member_field' => 'shipping_last_name'],
            'shipping_address1'   => ['title' => '', 'member_field' => 'shipping_address'],
            'shipping_address2'   => ['title' => '', 'member_field' => 'shipping_address2'],
            'shipping_city'       => ['title' => '', 'member_field' => 'shipping_city'],
            'shipping_state'      => ['title' => '', 'member_field' => 'shipping_state'],
            'shipping_country'    => ['title' => '', 'member_field' => 'shipping_country'],
            'shipping_postcode'   => ['title' => '', 'member_field' => 'shipping_zip'],
            'shipping_phone'      => ['title' => '', 'member_field' => 'shipping_phone'],
            'shipping_company'    => ['title' => '', 'member_field' => 'shipping_company'],
            'order_email'         => ['title' => '', 'member_field' => 'email'],
            'order_custom1'       => ['title' => '', 'member_field' => ''],
            'order_custom2'       => ['title' => '', 'member_field' => ''],
            'order_custom3'       => ['title' => '', 'member_field' => ''],
            'order_custom4'       => ['title' => '', 'member_field' => ''],
            'order_custom5'       => ['title' => '', 'member_field' => ''],
            'order_custom6'       => ['title' => '', 'member_field' => ''],
            'order_custom7'       => ['title' => '', 'member_field' => ''],
            'order_custom8'       => ['title' => '', 'member_field' => ''],
            'order_custom9'       => ['title' => '', 'member_field' => ''],
        ];
    }

    public function is_super_admin()
    {
        return $this->ee->session->userdata('group_id') == 1;
    }

    public function security()
    {
        $security_defaults = ['can_access_settings', 'can_add_payments'];
        $result = [];
        $security = $this->ee->config->item('store_security') ?: [];

        if (is_string($security)) {
            $security = json_decode($security, true);
        }

        foreach ($security_defaults as $key) {
            $result[$key] = (isset($security[$key]) and is_array($security[$key])) ? $security[$key] : [];
        }

        return $result;
    }

    public function has_privilege($privilege)
    {
        if ($this->is_super_admin()) {
            return true;
        }

        if ($privilege == 'can_access_inventory') {
            $store_channels = $this->ee->store->store->get_store_channels();
            $assigned_channels = $this->ee->functions->fetch_assigned_channels();
            return array_intersect($store_channels, $assigned_channels) == $store_channels;
        }

        $security = $this->security();
        if (!isset($security[$privilege])) {
            return false;
        }

        return in_array($this->ee->session->userdata('group_id'), $security[$privilege]);
    }

    public function config_json()
    {
        $items = [];
        foreach (['store_currency_symbol', 'store_currency_decimals',
                     'store_currency_thousands_sep', 'store_currency_dec_point',
                     'store_currency_suffix', ] as $key) {
            $items[$key] = config_item($key);
        }

        return json_encode($items);
    }

    /**
     * Add Store javascript & css to CP page header
     */
    public function load_cp_assets()
    {
        $this->ee->cp->add_to_head('<link rel="stylesheet" type="text/css" href="' . $this->asset_url('store.cp.css') . '" />');
        $this->ee->cp->add_to_foot('<script type="text/javascript" src="' . $this->asset_url('store.cp.js') . '"></script>');
    }

    public function asset_url($filename)
    {
        return URL_THIRD_THEMES . 'store/' . $filename . '?v=' . STORE_VERSION;
    }

    public function assetUrl($filename = null)
    {
        if (defined('URL_THIRD_THEMES') === true) {
            $theme_url = URL_THIRD_THEMES;
        } else {
            $theme_url = config_item('theme_folder_url') . 'user/';
        }

        $theme_url .= 'store/';

        if ($filename) {
            $theme_url .= $filename . '?v=' . STORE_VERSION;
        }

        // Protocol Relative Paths please..
        $theme_url = str_replace(['http://', 'https://'], '//', $theme_url);

        return $theme_url;
    }
}
