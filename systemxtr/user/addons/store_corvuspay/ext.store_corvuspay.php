<?php

if (!defined('BASEPATH')) { exit('No direct script access allowed'); }

class Store_corvuspay_ext
{
    public $name = 'Store CorvusPay';
    public $version = '1';
    public $description = 'Adds CorvusPay (off-site) gateway to Exp:resso Store.';
    public $settings_exist = 'n';
    public $docs_url = '';
    /** Avoid PHP 8.2 dynamic properties deprecation */
    public $settings = array();

    public function __construct($settings = array())
    {
        $this->settings = (array) $settings;
    }

    public function activate_extension()
    {
        ee()->db->insert('extensions', array(
            'class'    => __CLASS__,
            'method'   => 'store_payment_gateways',
            'hook'     => 'store_payment_gateways',
            'settings' => serialize(array()),
            'priority' => 10,
            'version'  => $this->version,
            'enabled'  => 'y'
        ));

        return true;
    }

    public function update_extension($current = '')
    {
        if ($current == '' || $current == $this->version) {
            return false;
        }

        ee()->db->where('class', __CLASS__)
            ->update('extensions', array('version' => $this->version));

        return true;
    }

    public function disable_extension()
    {
        ee()->db->where('class', __CLASS__)
            ->delete('extensions');
        return true;
    }

    /**
     * Register the CorvusPay gateway with Store.
     * This must match the Omnipay gateway directory name under this add-on:
     *   Omnipay/CorvusPay/Gateway.php -> class Omnipay\CorvusPay\Gateway
     */
    public function store_payment_gateways($gateways)
    {
        // Call previous extensions if any
        if (ee()->extensions->last_call !== false) {
            $gateways = ee()->extensions->last_call;
        }

        // Load Store's Composer autoloader so we can use its Omnipay 3 dependencies
        $composer = require PATH_THIRD . 'store/autoload.php';

        // PSR-4 mapping for the driver bundled in this extension
        if (method_exists($composer, 'addPsr4')) {
            $composer->addPsr4('Omnipay\\CorvusPay\\', __DIR__ . '/Omnipay/CorvusPay/');
        } else {
            // Fallback for older Composer loaders
            $composer->add('Omnipay\\CorvusPay\\', __DIR__ . '/Omnipay/CorvusPay/');
            $composer->add('Omnipay', __DIR__ . '/Omnipay/');
        }

        // Register gateway name that will appear in Store
        // (must match Omnipay::create('CorvusPay'))
        $gateways[] = 'CorvusPay';

        return $gateways;
    }
}
