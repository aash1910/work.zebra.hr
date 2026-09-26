<?php

/*
 * Exp:resso Store module for ExpressionEngine
 * Copyright (c) 2010-2019 Exp:resso (support@exp-resso.com)
 */

namespace Store\Service;

use Member_register;
use Store\Model\Member;
use Store\Model\Order;
use Store\StubOutput;

class MemberService extends AbstractService
{
    protected $member_register;
    protected $old_post;
    protected $old_output;

    public function __construct($ee, $member_register = null)
    {
        parent::__construct($ee);

        $this->member_register = $member_register ?: new Member_register();
    }

    /**
     * Process member registration using cart data
     */
    public function register(Order $order)
    {
        // Register a member when explicitly requested via checkout, or when a password was provided
        if (!$order->member_id && ($order->register_member || $order->password_hash)) {
            $this->fake_post($order);
            $this->register_member_from_post();
            $this->update_member($order);
        }
    }

    /**
     * Fake POST data so we can re-use the Member_register class methods
     */
    public function fake_post(Order $order)
    {
        // save existing post data so we can put it back after
        $this->old_post = $_POST;

        $_POST = [];
        $_POST['email'] = $order->order_email;
        $_POST['username'] = $order->username ?: $order->order_email;
        $_POST['screen_name'] = $order->screen_name ?: $order->order_email;

        // generate random password, we will update it after member is created
        // capital letter ensures we meet EE complexity requirements
        $_POST['password'] = chr(rand(65, 90)) . md5(uniqid(mt_rand(), true));
        $_POST['password_confirm'] = $_POST['password'];
    }

    public function restore_post()
    {
        // restore POST data
        $_POST = $this->old_post;
    }

    public function fake_output()
    {
        // fake EE_Output library to prevent rending user message
        $this->old_output = ee()->output;
        ee()->remove('output');
        ee()->set('output', new StubOutput($this->old_output));
    }

    public function restore_output()
    {
        // restore EE_Output library
        ee()->remove('output');
        ee()->set('output', $this->old_output);
    }

    public function register_member_from_post()
    {
        // skip some of the boring stuff
        ee()->config->set_item('use_membership_captcha', 'n');
        ee()->config->set_item('require_terms_of_service', 'n');
        ee()->config->set_item('secure_forms', 'n');
        ee()->config->set_item('allow_member_registration', 'y');

        // run the member registration process in a non-redirecting mode
        // To prevent ExpressionEngine’s built‑in member registration flow from hard‑redirecting and
        // aborting our request during embedded flows (e.g., checkout, AJAX).
        // EE’s Member_register calls ee()->functions->redirect() after processing;
        // in our context that redirect would prematurely end the request and break Store’s flow.

        ee()->load->library('stats');

        $redirects = new RedirectControlService($this->ee);
        $redirects->turnOffRedirects();
        try {
            $this->member_register->register_member();
        } finally {
            $redirects->restoreRedirects();
        }
    }

    public function update_member(Order $order)
    {
        // find newly created member
        $member = Member::where('email', $order->order_email)->first();
        if ($member) {
            // Only override password if a password was actually provided during checkout
            if (!empty($order->password_hash)) {
                $member->password = $order->password_hash;
                $member->salt = $order->password_salt;
                $member->save();
            }

            // assign order to new member
            $order->member_id = $member->member_id;
            $order->save();

            // Grab all previous orders with the same email and assign them to the new member
            $orders = Order::where('order_email', $order->order_email)->whereNull('member_id')->get();

            foreach ($orders as $dborder) {
                $dborder->member_id = $member->member_id;
                $dborder->save();
            }
        }
    }

    public function get_member_fields_select()
    {
        $query = ee()->db
            ->select('m_field_id, m_field_name, m_field_label')
            ->from('member_fields')
            ->get()
            ->result_array();

        $member_fields = [];
        foreach ($query as $row) {
            $member_fields['m_field_id_' . $row['m_field_id']] = $row['m_field_label'];
        }

        // find Zoo Visitor fields
        $check_query = ee()->db
            ->select('module_id')
            ->from('modules')
            ->where(['module_name' => 'Visitor'])
            ->limit(1)
            ->get()
            ->result_array();

        if ($check_query) {
            //check if installed
            $installed_zoo = true;
        } else {
            $installed_zoo = false;
        }

        if (@$installed_zoo) {
            $query_name = ee()->db
                ->select('var_value')
                ->from('zoo_visitor_settings')
                ->where(['var' => 'member_channel_id'])
                ->get();

            $channel_id = $query_name->row('var_value');

            $query = ee()->db
                ->select('cf.field_id, cf.field_label')
                ->from('exp_channels_channel_fields c')
                ->join('exp_channel_fields cf', 'cf.field_id = c.field_id')
                ->where('channel_id', $channel_id)
                ->where_not_in('cf.field_type', ['zoo_visitor', 'zoo_plus', 'playa', 'matrix', 'channel_images', 'channel_files'])
                ->get()
                ->result_array();

            $zoo_fields = [];
            foreach ($query as $row) {
                $zoo_fields['field_id_' . $row['field_id']] = $row['field_label'];
            }

            // add zoo optgroup
            if (!empty($zoo_fields)) {
                $member_fields = [lang('store.optgroup_member_fields') => $member_fields];
                $member_fields[lang('store.optgroup_zoo_fields')] = $zoo_fields;
            }
        }

        return array_merge(['' => ''], array_filter($member_fields));
    }

    public function load_member_data($member_id)
    {
        // Standard member fields
        $member_data = ee()->db
            ->where('member_id', $member_id)
            ->get('member_data')
            ->row_array();

        // Zoo Visitor fields
        if (!empty(ee()->config->_global_vars['zoo_visitor_id'])) {
            foreach (ee()->config->_global_vars as $key => $value) {
                if (strpos($key, 'visitor:global:field_id_') === 0) {
                    $member_data[str_replace('visitor:global:', '', $key)] = $value;
                }
            }
        }

        return $member_data;
    }

    public function save_member_data($member_id, $data)
    {
        $order_fields = ee()->store->config->order_fields();

        // split out standard & channel member fields
        $member_fields = [];
        $channel_fields = [];

        // Get member field mappings (name to ID)
        $member_field_mappings = [];
        $query = ee()->db
            ->select('m_field_id, m_field_name')
            ->from('member_fields')
            ->get()
            ->result_array();

        foreach ($query as $row) {
            $member_field_mappings[$row['m_field_name']] = 'm_field_id_' . $row['m_field_id'];
        }

        foreach ($order_fields as $field_name => $field) {
            $member_field = $field['member_field'];

            // Skip if no member field is mapped
            if (empty($member_field)) {
                continue;
            }

            // Handle different member field formats
            if (strpos($member_field, 'm_field_id_') === 0) {
                // Already in correct format
                $member_fields[$member_field] = $data[$field_name];
            } elseif (strpos($member_field, 'field_id_') === 0) {
                // Channel field format
                $channel_fields[$member_field] = $data[$field_name];
            } elseif (isset($member_field_mappings[$member_field])) {
                // Field name format - convert to field ID format
                $member_fields[$member_field_mappings[$member_field]] = $data[$field_name];
            }
        }

        // update standard member fields
        if (!empty($member_fields)) {
            $update_member_d = ee('Model')->get('Member', $member_id)->first();
            $update_member_d->set($member_fields);
            $update_member_d->save();
        }

        // update Zoo Visitor fields
        if (!empty($channel_fields) and !empty(ee()->config->_global_vars['zoo_visitor_id']) and
            ee()->config->_global_vars['zoo_member_id'] == $member_id) {
            /*ee()->db->where('entry_id', ee()->config->_global_vars['zoo_visitor_id'])
                ->update('channel_data', $channel_fields);*/
            $entry = ee('Model')->get('ChannelEntry')
                ->with('Channel')
                ->filter('entry_id', ee()->config->_global_vars['zoo_visitor_id'])
                ->first();

            $entry->set($channel_fields);
            $entry->save();
        }
    }
}
