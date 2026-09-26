<?php

/*
 * Exp:resso Store module for ExpressionEngine
 * Copyright (c) 2010-2014 Exp:resso (support@exp-resso.com)
 */

namespace Store\Action;

use Store\Exception\CartException;
use Store\FormValidation;
use Store\Model\Order;

class CheckoutAction extends AbstractAction
{
    public static $form_errors;

    public function perform()
    {
        ee()->lang->loadfile('myaccount');

        // don't submit order when submitting add to cart form
        if (!empty($_POST['nosubmit'])) {
            unset($_POST['submit']);
        }

        if (isset($_POST['entry_id'])) {
            // simple add to cart form, add details to items array
            $_POST['items'] = [$_POST];
        }

        if (ee()->input->get_post('empty_cart')) {
            ee()->store->orders->clear_cart_cookie();

            // are there any items to add after emptying the cart?
            $add_items = false;
            if (isset($_POST['items'])) {
                foreach ($_POST['items'] as $item) {
                    if (isset($item['entry_id'])) {
                        $add_items = true;
                    }
                }
            }

            // only finish now if there are no new products to add to the cart
            if (!$add_items) {
                $return_url = ee()->store->store->create_url(ee()->input->get_post('RET'));
                return ee()->functions->redirect($return_url);
            }
        }

        $form_params = $this->form_params();

        $update_data = ee('Security/XSS')->clean($_POST);

        $cart = ee()->store->orders->get_cart();

        // Some doublecheck to remove any html
        foreach ($update_data as $key => $val) {
            if (strpos($key, 'billing_') === false && strpos($key, 'shipping_') === false) {
                continue;
            }

            $val = html_entity_decode($val);
            $val = str_replace('[removed]', '', $val);
            // Strip unnecessary slashes and quotes to prevent double-escaping
            if (is_string($val)) {
                $val = stripslashes($val);
                $val = trim($val, '"\\');
            }
            $update_data[$key] = strip_tags($val);
        }

        $order_fields = ee()->store->config->order_fields();

        $path = PATH_ADDONS . 'member/mod.member_settings.php';

        if (! class_exists('\Member_settings')) {
            require $path;
        }

        $MS = new \Member_settings();

        //var_dump($MS);
        $member_id = ee()->session->userdata('member_id');

        $member_field_prefix = "exp_member_data_field_";
        $column_name_prefix = "m_field_id_";

        if (!$member_id || !is_int($member_id)) {
            // do nothing they are guest or not logged in
        } else {

            $all_member_fields_found = array();
            foreach($update_data as $key => $val) {
                if ( isset($order_fields[$key])) {
                    $member_field_name = $order_fields[$key]['member_field'];
                    $all_member_fields_found [$member_field_name] = $val;
                }
            }

            if (isset($all_member_fields_found[''])) {
                unset($all_member_fields_found['']);
            }

            $sql = "SELECT m_field_id, m_field_label FROM exp_member_fields";
            $query = ee()->db->query($sql);

            foreach($query->result_array() as $row) {
                if (array_key_exists($row['m_field_label'], $all_member_fields_found)) {
                    // update the member field data
                    $sql_update = "UPDATE " . $member_field_prefix . $row['m_field_id'] . " SET " . $column_name_prefix . $row['m_field_id'] . " = '" . $all_member_fields_found[$row['m_field_label']] . "' WHERE member_id = " . $member_id;
                    $query_update = ee()->db->query($sql_update);
                }
            }

        }

        try {
            $cart->fill($update_data, $form_params);
        } catch (CartException $e) {
            ee()->output->show_user_error(false, ['Store: ' . $e->getMessage()]);
        }
        // remember whether return_url in cart should be https
        if (isset($update_data['return_url'])) {
            $cart->return_url = $this->get_return_url();
        }
        $cart->cancel_url = ee()->store->store->create_url();

        // validate form input
        $address_fields = [
            'name', 'first_name', 'last_name', 'address1', 'address2', 'address3', 'city', 'state', 'region', 'country',
            'postcode', 'phone', 'company',
        ];

        $form_validation = new FormValidation();

        $rules = [
            [
                    'field' => 'payment_method',
                    'label' => 'lang:store.payment_method',
                    'rules' => 'valid_payment_method',
            ],
        ];

        // Check if shipping is required by looking at available shipping methods and order shipping quantity
        $shipping_methods = ee()->store->shipping->get_order_shipping_methods($cart);
        $requires_shipping = !empty($shipping_methods) && $cart->order_shipping_qty > 0;

        if ($requires_shipping) {
            $rules[] = [
                    'field' => 'shipping_method',
                    'label' => 'lang:store.shipping_method',
                    'rules' => 'valid_shipping_method',
            ];
        }

        foreach ($address_fields as $field) {
            // shorthand for requiring both billing and shipping fields

            if (isset($form_params['rules:' . $field])) {
                $rules_add = $form_params['rules:' . $field];
                $form_params['rules:billing_' . $field] = $rules_add;
                $form_params['rules:shipping_' . $field] = $rules_add;

                // Only require shipping fields if the cart actually requires shipping
                if (!$cart->shipping_same_as_billing && $requires_shipping) {
                    $rules[] =
                    [
                            'field' => 'shipping_' . $field,
                            'label' => 'lang:store.shipping_' . $field,
                            'rules' => 'required',
                    ];
                }
                if (!$cart->billing_same_as_shipping) {
                    $rules[] =
                    [
                            'field' => 'billing_' . $field,
                            'label' => 'lang:store.billing_' . $field,
                            'rules' => 'required',
                    ];
                }
            }
        }

        // on final checkout step, payment_method is required
        if (isset($update_data['submit'])) {
            $rules[] = [
                'field' => 'payment_method',
                'label' => 'lang:store.payment_method',
                'rules' => 'required',
            ];
        }

        // accept terms checkbox
        if (isset($update_data['accept_terms'])) {
            $rules[] = [
                'field' => 'accept_terms',
                'label' => 'lang:accept_terms',
                'rules' => 'require_accept_terms',
            ];
        }

        // validate email address
        if (isset($update_data['order_email'])) {
            $rules[] = ['field'        => 'order_email',
                               'label' => 'lang:store.order_email',
                               'rules' => 'required', ];
        }

        // if registering member, ensure email does not already exist
        if ($cart->register_member) {
            $rules[] = [
                'field' => 'order_email',
                'label' => 'lang:store.order_email',
                'rules' => 'valid_user_email',
            ];
            $rules[] = [
                'field' => 'username',
                'label' => 'lang:username',
                'rules' => 'valid_username',
            ];
            // $rules[] = [
            //     'field' => 'screen_name',
            //     'label' => 'lang:screen_name',
            //     'rules' => 'valid_screen_name',
            // ];
            $rules[] = [
                'field' => 'password',
                'label' => 'lang:password',
                'rules' => 'valid_password',
            ];
            $rules[] = [
                'field' => 'password_confirm',
                'label' => 'lang:password',
                'rules' => 'matches[password]',
            ];
        }

        // validate promo code
        if (isset($update_data['promo_code'])) {
            $rules[] = [
                'field' => 'promo_code',
                'label' => 'lang:store.promo_code',
                'rules' => 'valid_promo_code',
            ];
        }

        $form_validation->set_rules($rules);

        /*
         * store_checkout_form_validation hook
         * @since 2.4.5
         */
        if (ee()->extensions->active_hook('store_checkout_form_validation')) {
            // Since we can't guarantee the extension methods exist, just continue without calling them
            // The hook functionality may need to be updated separately
        }

        ee()->load->library('logger');

        if ($form_validation->run() == true) {
            // Clean ALL cart data fields - very aggressively fix any string data
            foreach (get_object_vars($cart) as $field => $value) {
                if (is_string($value)) {
                    // Strip ALL backslashes and quotes to prevent DB truncation errors
                    $sanitized = str_replace(['\\', '"', "\\\""], '', $value);
                    $cart->{$field} = $sanitized;
                }
            }

            // Extra special cleaning for known problematic fields
            $fields_to_clean = [
                'billing_country', 'billing_state',
                'shipping_country', 'shipping_state',
                'shipping_method'
            ];

            foreach ($fields_to_clean as $field) {
                if (isset($cart->{$field})) {
                    // Make sure these fields are completely clean
                    $cart->{$field} = preg_replace('/[^a-zA-Z0-9 \-_]/', '', $cart->{$field});
                }
            }

            // update cart
            $cart->recalculate();
            ee()->store->orders->set_cart_cookie();

            // where to next?
            if (!$cart->isEmpty() && isset($_POST['submit'])) {
                // prevent duplicate payment form submissions
                $this->invalidate_csrf_token();
                if (config_item('store_force_member_login') && empty(ee()->session->userdata['member_id']) && !$cart->register_member) {
                    // admin has set order submission to members only,
                    // but customer is not logged in
                    ee()->output->show_user_error(false, [lang('store.submit_order_not_logged_in')]);
                }

                // set submit cookie (triggers conversion tracking code on order summary page)
                if (function_exists('set_cookie')) {
                    set_cookie('store_cart_submit', $cart->order_hash, 0);
                } else {
                    // Fallback for cookie setting
                    $_COOKIE['store_cart_submit'] = $cart->order_hash;
                }

                if ($cart->is_order_paid) {
                    // skip payment for free orders
                    $cart->markAsComplete();
                    return ee()->functions->redirect($cart->parsed_return_url);
                }
                // submit to payment gateway (this will either redirect to a third party site,
                // or the order's return or cancel url)
                $credit_card = ee()->input->get_post('payment');
                $transaction = ee()->store->payments->new_transaction($cart);
                //$transaction->amount = $cart->order_owing;
                $transaction->amount = $cart->order_total;
                $transaction->payment_method = $cart->payment_method;
                ee()->store->payments->process_payment($cart, $transaction, $credit_card);
            } elseif (!$cart->isEmpty() && isset($_POST['next']) && isset($_POST['next_url'])) {
                $nextUrl = $this->get_return_url('next_url');
                ee()->functions->redirect($nextUrl);
            }

            // AJAX requests return JSON
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                if (ee()->config->item('store_new_json_response') == 'yes') {
                    // New JSON Response
                    $this->returnJsonResponse($cart);
                }
                ee()->output->send_ajax_response($cart->toTagArray());
            }

            // default is to update totals and return
            if (empty($_POST['nosubmit'])) {
                $return_url = ee()->store->store->create_url(ee()->input->get_post('RET'));
            } else {
                $return_url = $this->get_return_url();
            }
            ee()->functions->redirect($return_url);
        }

        static::$form_errors = $form_validation->error_array();
        // Set each error as flashdata and session cache for tag compatibility
        foreach (static::$form_errors as $field => $message) {
            ee()->session->set_flashdata($field, $message);
            ee()->session->set_cache('store_checkout_errors', $field, $message);
        }

        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            // New JSON Response
            if (ee()->config->item('store_new_json_response') == 'yes') {
                $this->returnJsonResponse($cart);
            }

            ee()->output->send_ajax_response(array_merge($cart->toTagArray(), $form_errors));
        }

        if ($this->form_param('error_handling') != 'inline') {
            ee()->output->show_user_error(false, static::$form_errors);
        }


        return ee()->core->generate_page();
    }

    private function returnJsonResponse($cart)
    {
        $out = [];
        $out['cart'] = $cart->toTagArray();

        if (!empty(static::$form_errors)) {
            $out['form_errors'] = static::$form_errors;
        }

        ee()->output->send_ajax_response($out);
    }
}
