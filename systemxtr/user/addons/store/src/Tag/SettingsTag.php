<?php

namespace Store\Tag;

use Store\Model\PaymentMethod;

class SettingsTag extends AbstractTag
{
    public function parse()
    {
        $value = '';

        $setting = $this->param('setting');
        if ($setting) {
            // Special case for Stripe publishable key
            if ($setting === 'stripe_publishable_key') {
                $method = \Store\Model\PaymentMethod::where('site_id', config_item('site_id'))
                    ->where('class', 'Stripe_PaymentIntents')
                    ->first();
                if ($method) {
                    $settings = $method->settings;
                    if (isset($settings['publishableKey'])) {
                        $value = $settings['publishableKey'];
                    }
                }
            } else {
                // Try to find the setting in any enabled payment method
                $methods = \Store\Model\PaymentMethod::where('site_id', config_item('site_id'))->get();
                foreach ($methods as $method) {
                    $settings = $method->settings;
                    if (isset($settings[$setting])) {
                        $value = $settings[$setting];
                        break;
                    }
                }
                // If not found in payment methods, try config items array
                if ($value === '') {
                    $config_items = ee()->store->config->items();
                    if (isset($config_items[$setting])) {
                        $value = $config_items[$setting];
                    }
                }
            }
        }
        return $value;
    }
}
