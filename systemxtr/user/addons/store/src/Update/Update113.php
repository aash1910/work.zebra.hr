<?php

/*
 * Exp:resso Store module for ExpressionEngine
 * Copyright (c) 2010-2019 Exp:resso (support@exp-resso.com)
 */

namespace Store\Update;

use Store\Update;

class Update113
{
    /**
     * Update all tables to be MSM-compatible
     */
    public function up()
    {
        $this->EE = get_instance();

        // get list of existing sites
        $sites = ee()->db->select('site_id')->get('sites')->result_array();
        if (empty($sites)) {
            $sites = array(1);
        } else {
            foreach ($sites as $key => $site) {
                $sites[$key] = $site['site_id'];
            }
        }

        // try to determine which site Store is currently using
        $site_id = (int)config_item('site_id');
        $product = ee()->db->select('channel_titles.site_id')
            ->from('store_products')
            ->join('channel_titles', 'channel_titles.entry_id = store_products.entry_id')
            ->limit(1)->get()->row_array();
        if (!empty($product)) {
            $site_id = (int)$product['site_id'];
        }

        // add msm-compatible store config table
        if (!ee()->db->table_exists('store_config')) {
            ee()->dbforge->add_field(array(
                'site_id' => array('type' => 'int', 'constraint' => 5, 'null' => false),
                'store_preferences' => array('type' => 'text', 'null' => true),
            ));

            ee()->dbforge->add_key('site_id', true);
            ee()->dbforge->create_table('store_config');

            // copy existing config to new table
            ee()->db->where('module_name', 'Store');
            $row = ee()->db->get('modules')->row_array();

            $config = array('site_id' => $site_id);
            if (!empty($row['settings'])) {
                $config['store_preferences'] = $row['settings'];
            }
            ee()->db->insert('store_config', $config);
        }

        // add site_id to store_carts
        if (!ee()->db->field_exists('site_id', 'store_carts')) {
            ee()->dbforge->add_column('store_carts', array(
                'site_id' => array('type' => 'int', 'constraint' => 5, 'null' => false),
            ), 'cart_id');
        }
        ee()->db->where('site_id', 0)->set('site_id', $site_id)->update('store_carts');

        // add site_id to store_countries
        if (!ee()->db->field_exists('site_id', 'store_countries')) {
            ee()->dbforge->add_column('store_countries', array(
                'site_id' => array('type' => 'int', 'constraint' => 5, 'null' => false),
            ));
            ee()->db->where('site_id', 0)->set('site_id', $site_id)->update('store_countries');
            ee()->db->query('ALTER TABLE ' . ee()->db->protect_identifiers('store_countries', true) .
                ' DROP PRIMARY KEY, ADD PRIMARY KEY(site_id,country_code);');
        }

        // add site_id to store_email_templates
        if (!ee()->db->field_exists('site_id', 'store_email_templates')) {
            ee()->dbforge->add_column('store_email_templates', array(
                'site_id' => array('type' => 'int', 'constraint' => 5, 'null' => false),
            ), 'template_id');
        }
        ee()->db->where('site_id', 0)->set('site_id', $site_id)->update('store_email_templates');

        // add site_id to store_orders
        if (!ee()->db->field_exists('site_id', 'store_orders')) {
            ee()->dbforge->add_column('store_orders', array(
                'site_id' => array('type' => 'int', 'constraint' => 5, 'null' => false),
            ), 'order_id');
        }
        ee()->db->where('site_id', 0)->set('site_id', $site_id)->update('store_orders');

        // add site_id to store_order_statuses
        if (!ee()->db->field_exists('site_id', 'store_order_statuses')) {
            ee()->dbforge->add_column('store_order_statuses', array(
                'site_id' => array('type' => 'int', 'constraint' => 5, 'null' => false),
            ), 'order_status_id');
        }
        ee()->db->where('site_id', 0)->set('site_id', $site_id)->update('store_order_statuses');

        // add site_id to store_plugins
        if (!ee()->db->field_exists('site_id', 'store_plugins')) {
            ee()->dbforge->add_column('store_plugins', array(
                'site_id' => array('type' => 'int', 'constraint' => 5, 'null' => false),
            ), 'plugin_instance_id');
        }
        ee()->db->where('site_id', 0)->set('site_id', $site_id)->update('store_plugins');

        // add site_id to store_promo_codes
        if (!ee()->db->field_exists('site_id', 'store_promo_codes')) {
            ee()->dbforge->add_column('store_promo_codes', array(
                'site_id' => array('type' => 'int', 'constraint' => 5, 'null' => false),
            ), 'promo_code_id');
        }
        ee()->db->where('site_id', 0)->set('site_id', $site_id)->update('store_promo_codes');

        // add site_id to store_regions
        if (!ee()->db->field_exists('site_id', 'store_regions')) {
            ee()->dbforge->add_column('store_regions', array(
                'site_id' => array('type' => 'int', 'constraint' => 5, 'null' => false),
            ), 'country_code');
            ee()->db->where('site_id', 0)->set('site_id', $site_id)->update('store_regions');
            ee()->db->query('ALTER TABLE ' . ee()->db->protect_identifiers('store_regions', true) .
                ' DROP PRIMARY KEY, ADD PRIMARY KEY(site_id,country_code,region_code);');
        }

        // add site_id to store_tax_rates
        if (!ee()->db->field_exists('site_id', 'store_tax_rates')) {
            ee()->dbforge->add_column('store_tax_rates', array(
                'site_id' => array('type' => 'int', 'constraint' => 5, 'null' => false),
            ), 'tax_id');
        }
        ee()->db->where('site_id', 0)->set('site_id', $site_id)->update('store_tax_rates');
    }
}
