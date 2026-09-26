<?php

class Store_check_ext
{
    public $name = 'Store Check Payments';
    public $version = '1.3.0';
    public $description = 'On-site manual payment method (uplatnica/virman) for Expresso Store';
    public $settings_exist = 'n';

    public function activate_extension()
    {
        $data = array(
            'class'     => __CLASS__,
            'method'    => 'store_payment_gateways',
            'hook'      => 'store_payment_gateways',
            'settings'  => serialize(array()),
            'priority'  => 10,
            'version'   => $this->version,
            'enabled'   => 'y'
        );
        ee()->db->insert('extensions', $data);
    }

    public function disable_extension()
    {
        ee()->db->where('class', __CLASS__)->delete('extensions');
    }

    public function update_extension($current = '')
    {
        if ($current == '' || $current == $this->version) {
            return FALSE;
        }
        ee()->db->where('class', __CLASS__)
            ->update('extensions', array('version' => $this->version));
    }

    public function store_payment_gateways($gateways)
    {
        if (ee()->extensions->last_call !== FALSE) {
            $gateways = ee()->extensions->last_call;
        }

        if (!in_array('Check', $gateways, true)) {
            $gateways[] = 'Check';
        }

        // Use Store vendor-prefixed autoloader
        $loader = require PATH_THIRD.'store/vendor-build/autoload.php';

        // PSR-4 map Omnipay\Check\ to our src/
        $src = __DIR__ . '/src/';
        if (is_dir($src)) {
            if (method_exists($loader, 'addPsr4')) {
                $loader->addPsr4('Omnipay\\Check\\', $src);
            } else {
                $loader->add('Omnipay\\Check\\', $src);
            }
        }

        // Ensure Manual gateway is aliased even if only prefixed class exists
        if (!class_exists('Omnipay\\Manual\\Gateway', false) && class_exists('Store\\Dependency\\Omnipay\\Manual\\Gateway')) {
            class_alias('Store\\Dependency\\Omnipay\\Manual\\Gateway', 'Omnipay\\Manual\\Gateway');
        }

        return $gateways;
    }
}
