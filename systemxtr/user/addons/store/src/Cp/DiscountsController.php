<?php

/*
 * Exp:resso Store module for ExpressionEngine
 * Copyright (c) 2010-2019 Exp:resso (support@exp-resso.com)
 */

namespace Store\Cp;

use Store\FormBuilder;
use Store\Model\Discount;

class DiscountsController extends AbstractController
{
    public function index()
    {
        $title = lang('nav_discounts');
        $this->addBreadcrumb(store_cp_url('discounts'), lang('nav_promotions'));
        // handle form submit
        if (!empty($_POST['submit'])) {
            $selected = Discount::where('site_id', config_item('site_id'))->whereIn('id', (array)ee()->input->post('selected'));

            switch (ee()->input->post('with_selected')) {
                case 'enable':
                    $selected->update(['enabled' => 1]);
                    break;
                case 'disable':
                    $selected->update(['enabled' => 0]);
                    break;
                case 'delete':
                    $selected->delete();
                    break;
            }

            ee()->session->set_flashdata(['message_success' => lang('store.settings.updated')]);
            ee()->functions->redirect(store_cp_url('discounts'));
        }

        // sortable ajax post
        if (!empty($_POST['sortable_ajax'])) {
            return $this->sortableAjax('\Store\Model\Discount');
        }

        $data = [];
        $data['post_url'] = store_cp_url() . '&sc=discounts';
        $data['edit_url'] = store_cp_url('discounts', 'edit') . '&id=';
        $data['discounts'] = Discount::where('site_id', config_item('site_id'))->orderBy('sort')->get();

        return [
            'body'       => $this->render('discounts/index', $data),
            'breadcrumb' => $this->getBreadcrumbs(),
            'heading'    => $title,
        ];
    }

    public function edit()
    {
        $this->addBreadcrumb(store_cp_url('discounts'), lang('nav_discounts'));

        $discount_id = ee()->input->get('id');

        if ($discount_id == 'new') {
            $discount = new Discount();
            $discount->site_id = config_item('site_id');
            $discount->enabled = 1;
            $discount->break = 1;
            $this->setTitle(lang('store.discount_new'));
        } else {
            $discount = Discount::where('site_id', config_item('site_id'))->find($discount_id);

            if (empty($discount)) {
                return show_404();
            }
            $this->setTitle(lang('store.discount_edit'));
        }
        // handle form submit
        $discount->fill((array)ee()->input->post('discount'));

        $rules = [
            [
                'field' => 'discount[name]',
                'label' => 'name',
                'rules' => 'required',
            ],
        ];
        ee()->load->library('form_validation');
        ee()->form_validation->set_rules($rules);

        if (ee()->form_validation->run() == true) {
            $discount->save();

            ee()->session->set_flashdata('message_success', lang('store.settings.updated'));
            ee()->functions->redirect(store_cp_url('discounts'));
        }

        $data = [];
        $data['post_url'] = store_cp_url('discounts', 'edit', ['id' => $discount_id]);
        $data['discount'] = $discount;
        $data['form'] = new FormBuilder($discount);
        $data['category_options'] = ee()->store->products->get_categories();
        //$data['product_options'] = ee()->store->products->get_product_titles();
        $data['product_options'] = $this->get_product_titles($discount->entry_ids);

        $get_store_channels = $this->get_store_channels();
    
        $data['channel_options'] = array();
        foreach ($get_store_channels as $row) {
            $data['channel_options'][$row['channel_id']] = $row['channel_title'];
        }

        $get_field_list = $this->get_field_list();

        $data['field_options'] = array();
        foreach ($get_field_list as $row) {
            $data['field_options'][$row['field_id']] = $row['field_label'];
        }

        $get_payment_methods = $this->get_payment_methods();

        $data['payment_method_options'] = array('' => lang('store.none'));
        foreach ($get_payment_methods as $row) {
            $data['payment_method_options'][$row['class']] = $row['class'];
        }

        $data['member_roles'] = ee('Model')->get('Role')
            // ignore banned, pending
            ->filter('role_id', 'NOT IN', [2, 4])
            ->all()
            ->getDictionary('role_id', 'name');

        ee()->cp->add_js_script(['ui' => 'datepicker']);

        return $this->render('discounts/edit', $data);
    }

    public function get_payment_methods()
    {
        $this->ee->db->select("class");
        $this->ee->db->order_by("class", "asc");
        $this->ee->db->where("enabled", 1);
        $query = $this->ee->db->get('exp_store_payment_methods');
    
        return $query->result_array();
    }

    /**
     * Quick list of limited products for use of searching in multi select
     */
    
    public function get_product_list()
    {
        $q = $this->ee->input->get_post('q');
        $limit = $this->ee->input->get_post('limit');
        $q = isset($q) ? $q : "";
        $limit = isset($limit) ? $limit : "";
        $q_escaped = $this->ee->db->escape_like_str(strtolower($q));
    
        $sku_table = 'exp_channel_data_field_58';
        $sku_column = 'field_id_58';
    
        $table_check = $this->ee->db->query("SHOW TABLES LIKE '{$sku_table}'");
        $has_sku_table = ($table_check->num_rows() > 0);
    
        if ($has_sku_table) {
            $this->ee->db->select("exp_channel_titles.entry_id, CONCAT_WS(' : ', exp_channel_titles.title, {$sku_table}.{$sku_column}) AS title", FALSE);
            $this->ee->db->where("(LOWER(exp_channel_titles.title) LIKE '%{$q_escaped}%' OR LOWER({$sku_table}.{$sku_column}) LIKE '%{$q_escaped}%')", NULL, FALSE);
            $this->ee->db->join('exp_channel_titles', 'exp_channel_titles.entry_id = exp_store_products.entry_id');
            $this->ee->db->join($sku_table, "{$sku_table}.entry_id = exp_store_products.entry_id", 'left');
        } else {
            $this->ee->db->select('exp_channel_titles.entry_id, exp_channel_titles.title AS title', FALSE);
            $this->ee->db->where("LOWER(exp_channel_titles.title) LIKE '%{$q_escaped}%'", NULL, FALSE);
            $this->ee->db->join('exp_channel_titles', 'exp_channel_titles.entry_id = exp_store_products.entry_id');
        }
    
        $this->ee->db->order_by('exp_channel_titles.title', 'asc');
        $this->ee->db->limit($limit);
        $query = $this->ee->db->get('exp_store_products');
    
        return $this->ee->output->send_ajax_response($query->result_array());
    }

    public function get_product_titles($entry_ids)
    {
        $products = array();
    
        $query = $this->ee->store->db->table('store_products')
            ->join('channel_titles', 'channel_titles.entry_id', '=', 'store_products.entry_id')
            ->select(array('channel_titles.entry_id', 'channel_titles.title'))
            ->where('site_id', config_item('site_id'))
            ->orderBy('channel_titles.title')
            ->limit(50)
            ->get();
    
        foreach ($query as $row) {
            $products[$row->entry_id] = $row->title;
        }
    
        if (!empty($entry_ids)) {
            $query = $this->ee->store->db->table('store_products')
                ->join('channel_titles', 'channel_titles.entry_id', '=', 'store_products.entry_id')
                ->select(array('channel_titles.entry_id', 'channel_titles.title'))
                ->where('site_id', config_item('site_id'))
                ->whereIn('channel_titles.entry_id', $entry_ids)
                ->orderBy('channel_titles.title')
                ->get();
    
            foreach ($query as $row) {
                $products[$row->entry_id] = $row->title;
            }
        }
    
        return $products;
    }

    public function get_store_channels()
    {
        // get all store field ids
        $this->ee->db->select("field_id");
        $this->ee->db->where("field_type", "store");
        $field_ids = array_column(
            $this->ee->db->get('exp_channel_fields')->result_array(),
            'field_id'
        );
    
        if (empty($field_ids)) return [];
    
        // path 1: directly assigned channels
        $this->ee->db->select("channel_id");
        $this->ee->db->where_in("field_id", $field_ids);
        $direct_channel_ids = array_column(
            $this->ee->db->get('exp_channels_channel_fields')->result_array(),
            'channel_id'
        );
    
        // path 2: channels via field group
        $this->ee->db->select("group_id");
        $this->ee->db->where_in("field_id", $field_ids);
        $group_ids = array_column(
            $this->ee->db->get('exp_channel_field_groups_fields')->result_array(),
            'group_id'
        );
    
        $group_channel_ids = [];
        if (!empty($group_ids)) {
            $this->ee->db->select("channel_id");
            $this->ee->db->where_in("group_id", $group_ids);
            $group_channel_ids = array_column(
                $this->ee->db->get('exp_channels_channel_field_groups')->result_array(),
                'channel_id'
            );
        }
    
        // merge and deduplicate
        $all_channel_ids = array_unique(array_merge($direct_channel_ids, $group_channel_ids));
    
        if (empty($all_channel_ids)) return [];
    
        // get channel titles
        $this->ee->db->select("channel_id, channel_title");
        $this->ee->db->where_in("channel_id", $all_channel_ids);
        $this->ee->db->order_by("channel_title", "asc");
    
        return $this->ee->db->get('exp_channels')->result_array();
    }
    
    public function get_store_channel_ids()
    {
        $channels = $this->get_store_channels();
        return array_column($channels, 'channel_id');
    }
    
    public function get_field_list()
    {
        $channel_ids = $this->get_store_channel_ids();
    
        if (empty($channel_ids)) return [];
    
        // get all text field ids that are directly assigned to store channels
        $this->ee->db->select("field_id");
        $this->ee->db->where_in("channel_id", $channel_ids);
        $direct_field_ids = array_column(
            $this->ee->db->get('exp_channels_channel_fields')->result_array(),
            'field_id'
        );
    
        // get all text field ids assigned via groups to store channels
        $this->ee->db->select("group_id");
        $this->ee->db->where_in("channel_id", $channel_ids);
        $group_ids = array_column(
            $this->ee->db->get('exp_channels_channel_field_groups')->result_array(),
            'group_id'
        );
    
        $group_field_ids = [];
        if (!empty($group_ids)) {
            $this->ee->db->select("field_id");
            $this->ee->db->where_in("group_id", $group_ids);
            $group_field_ids = array_column(
                $this->ee->db->get('exp_channel_field_groups_fields')->result_array(),
                'field_id'
            );
        }
    
        $all_field_ids = array_unique(array_merge($direct_field_ids, $group_field_ids));
    
        if (empty($all_field_ids)) return [];
    
        // finally get only text fields from that set
        $this->ee->db->select("field_id, field_label");
        $this->ee->db->where("field_type", "text");
        $this->ee->db->where("site_id", 0);
        $this->ee->db->where_in("field_id", $all_field_ids);
        $this->ee->db->order_by("field_label", "asc");
    
        return $this->ee->db->get('exp_channel_fields')->result_array();
    }

}
