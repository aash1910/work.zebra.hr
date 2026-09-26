<?php

/*
 * Exp:resso Store module for ExpressionEngine
 * Copyright (c) 2010-2019 Exp:resso (support@exp-resso.com)
 */

namespace Store;

use Store\Action\AbstractAction;
use Store\Tag\AbstractTag;
use Store\Dependency\Illuminate\Support\Str;
use Store\Model\PaymentMethod;

/**
 * Store module class
 *
 * This class is responsible for handling EE tags and actions.
 * No logic is kept in this class. Instead, all the logic is found in individual
 * Tag and Action classes. This keeps the code clean and makes testing easier.
 */
class Module
{
    protected $ee;

    public function __construct()
    {
        $this->ee = ee();

        // having a submit button named "submit" can cause JS issues
        // we provide "commit" as an alternative button name
        if (isset($_POST['commit'])) {
            $_POST['submit'] = $_POST['commit'];
        }
    }

    public function cart()
    {
        return $this->parse_tag('cart');
    }

    public function checkout()
    {
        return $this->parse_tag('checkout');
    }

    public function checkout_debug()
    {
        ee()->TMPL->tagdata = ee()->load->view('checkout_debug', null, true);

        return $this->parse_tag('checkout');
    }

    public function download()
    {
        return $this->parse_tag('download');
    }

    public function orders()
    {
        return $this->parse_tag('orders');
    }

    public function payment()
    {
        return $this->parse_tag('payment');
    }

    public function product()
    {
        return $this->parse_tag('product');
    }

    public function product_form()
    {
        return $this->parse_tag('product_form');
    }

    public function search()
    {
        return $this->parse_tag('search');
    }

    public function purchased()
    {
        return $this->parse_tag('purchased');
    }

    public function act_checkout()
    {
        return $this->perform_action('checkout');
    }

    public function act_download_file()
    {
        return $this->perform_action('download');
    }

    public function act_payment()
    {
        return $this->perform_action('payment');
    }

    public function act_payment_return()
    {
        return $this->perform_action('payment_return');
    }

    public function act_notification_handler()
    {
        return $this->perform_action('notification_handler');
    }

    public function gateways()
    {
        return $this->parse_tag('gateways');
    }

    public function settings()
    {
        return $this->parse_tag('settings');
    }

    protected function parse_tag($name)
    {
        $class = '\\Store\\Tag\\' . Str::studly($name) . 'Tag';

        /** @var AbstractTag $tag */
        $tag = new $class(ee()->TMPL->tagdata, ee()->TMPL->tagparams);

        return $tag->parse();
    }

    protected function perform_action($name)
    {
        $class = '\\Store\\Action\\' . Str::studly($name) . 'Action';

        /** @var AbstractAction $action */
        $action = new $class($this->ee);

        return $action->perform();
    }

    /**
     * Javascript for implementing stripe payment gateway
     *
     * @return string
     */
    public function stripe_js()
    {
        // If they don't specify publishable_api_key
        $publishable_api_key = ee()->TMPL->fetch_param('publishable_api_key');
        if (!$publishable_api_key) {
            // Try to get from Stripe PaymentMethod settings
            $method = PaymentMethod::where('site_id', config_item('site_id'))
                ->where('class', 'Stripe_PaymentIntents')
                ->first();
            if ($method) {
                $settings = $method->settings;
                if (isset($settings['publishableKey'])) {
                    $publishable_api_key = $settings['publishableKey'];
                }
            }
            if (!$publishable_api_key) {
                ee()->output->fatal_error('The Stripe "publishable_api_key" parameter is required, and no key was found in the Store payment method settings.');
            }
        }

        // Language is optional.  Default to English if language isn't specified
        $language = ee()->TMPL->fetch_param('language', 'en');

        return '<script type="text/javascript" src="https://js.stripe.com/v3/"></script>' . PHP_EOL .
                '<script type="text/javascript">const publishableApiKey = "' . $publishable_api_key . '";</script>' . PHP_EOL .
                '<script type="text/javascript">const language = "' . $language . '";</script>' . PHP_EOL .
                '<script type="text/javascript" src="' . ee()->store->config->assetUrl('js/stripe.js') . '"></script>';
    }
}
