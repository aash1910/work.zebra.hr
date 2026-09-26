<?php

/*
 * Exp:resso Store module for ExpressionEngine
 * Copyright (c) 2010-2019 Exp:resso (support@exp-resso.com)
 */

namespace Store\Cp;

use Store\FormBuilder;
use Store\Model\Sale;

class SalesController extends AbstractController
{
    public function __construct($ee)
    {
        parent::__construct($ee);

        $this->addBreadcrumb(store_cp_url('sales'), lang('nav_promotions'));
    }

    public function index()
    {
        // handle form submit
        if (!empty($_POST['submit'])) {
            $selected = Sale::where('site_id', config_item('site_id'))->whereIn('id', (array)ee()->input->post('selected'));

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

            ee()->functions->redirect(store_cp_url('sales'));
        }

        // sortable ajax post
        if (!empty($_POST['sortable_ajax'])) {
            return $this->sortableAjax('\Store\Model\Sale');
        }

        $data = [];
        $data['post_url'] = store_cp_url('sales');
        $data['edit_url'] = store_cp_url('sales', 'edit') . '&id=';
        $data['sales'] = Sale::where('site_id', config_item('site_id'))->orderBy('sort')->get();

        return [
            'body'       => $this->render('sales/index', $data),
            'breadcrumb' => $this->getBreadcrumbs(),
            'heading'    => lang('nav_sales'),
        ];
    }

    public function edit()
    {
        $this->addBreadcrumb(store_cp_url('sales', 'index'), lang('nav_sales'));

        $sale_id = ee()->input->get('id');
        if ($sale_id == 'new') {
            $sale = new Sale();
            $sale->site_id = config_item('site_id');
            $sale->enabled = 1;
            $title = lang('store.sale_new');
        } else {
            $sale = Sale::where('site_id', config_item('site_id'))->find($sale_id);

            if (empty($sale)) {
                return show_404();
            }

            $title = lang('store.sale_edit');
        }

        // handle form submit
        $sale->fill((array)ee()->input->post('sale'));

        //Change validation like below
        $rules = [
            [
                'field' => 'sale[name]',
                'label' => 'Sale Name',
                'rules' => 'required',
            ],
        ];

        ee()->load->library('form_validation');
        ee()->form_validation->set_rules($rules);
        if (ee()->form_validation->run() == true) {
            $sale->save();
            ee()->session->set_flashdata(['message_success' => lang('store.settings.updated')]);
            ee()->functions->redirect(store_cp_url('sales'));
        }

        $data = [];
        $data['post_url'] = store_cp_url('promotions', 'edit', ['id' => $sale_id]);
        $data['sale'] = $sale;
        $data['form'] = new FormBuilder($sale);
        $data['category_options'] = ee()->store->products->get_categories();
        //$data['product_options'] = ee()->store->products->get_product_titles();
        $data['product_options'] = $this->get_product_titles($data['sale']['entry_ids']);

        $data['member_roles'] = ee('Model')->get('Role')
            // ignore banned, guests, pending
            ->filter('role_id', 'NOT IN', [2, 4])
            ->all()
            ->getDictionary('role_id', 'name');

        ee()->cp->add_js_script(['ui' => 'datepicker']);

        return [
            'body'       => $this->render('sales/edit', $data),
            'breadcrumb' => $this->getBreadcrumbs(),
            'heading'    => $title,
        ];
    }

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

}
