<?php

/*
 * Exp:resso Store module for ExpressionEngine
 * Copyright (c) 2010-2019 Exp:resso (support@exp-resso.com)
 */

namespace Store\Update;

use Store\Update;

class Update150
{
    public function up()
    {
        $this->EE = get_instance();

        Update::register_action('act_payment_return');

        if (ee()->db->field_exists('cart_hash', 'store_carts')) {
            // remove redundant cart_hash column, store hash in cart_id
            ee()->dbforge->modify_column('store_carts', array(
                'cart_id' => array('name' => 'cart_id', 'type' => 'varchar', 'constraint' => 32, 'null' => false),
            ));

            ee()->db->query('UPDATE ' . ee()->db->protect_identifiers('store_carts', true) . '
                SET cart_id = cart_hash');

            ee()->dbforge->drop_column('store_carts', 'cart_hash');
        }

        if (!ee()->db->field_exists('payment_hash', 'store_payments')) {
            ee()->dbforge->add_column('store_payments', array(
                'payment_status' => array('type' => 'varchar', 'constraint' => 20, 'null' => false),
                'payment_hash' => array('type' => 'varchar', 'constraint' => 32, 'null' => false),
            ), 'order_id');

            ee()->db->query('UPDATE ' . ee()->db->protect_identifiers('store_payments', true) . '
                SET payment_status = "complete", payment_hash = MD5(RAND())');

            Update::create_index('store_payments', 'payment_hash', true);
        }

        if (!ee()->db->field_exists('payment_method_class', 'store_payments')) {
            ee()->dbforge->add_column('store_payments', array(
                'payment_method_class' => array('type' => 'varchar', 'constraint' => 50, 'null' => false),
            ), 'payment_method');

            ee()->db->query('UPDATE ' . ee()->db->protect_identifiers('store_payments', true) . '
                SET `payment_method_class` = CONCAT("Merchant_", `payment_method`)
                WHERE `payment_method_class` = "" AND `payment_method` != ""');
        }

        // txn_id is now known as reference, and stored as VARCHAR(255)
        if (ee()->db->field_exists('txn_id', 'store_payments')) {
            ee()->dbforge->modify_column('store_payments', array(
                'txn_id' => array('name' => 'reference', 'type' => 'varchar', 'constraint' => 255, 'null' => true),
            ));
        }

        // cart_id is now same as order_hash
        Update::drop_column_if_exists('store_orders', 'cart_id');

        // this field temporarily existed in 1.4.2.1
        Update::drop_column_if_exists('store_payments', 'cart_id');

        if (!ee()->db->field_exists('cancel_url', 'store_orders')) {
            ee()->dbforge->add_column('store_orders', array(
                'cancel_url' => array('type' => 'varchar', 'constraint' => 255),
            ), 'return_url');
        }

        if (!ee()->db->field_exists('order_completed_date', 'store_orders')) {
            ee()->dbforge->add_column('store_orders', array(
                'order_completed_date' => array('type' => 'int', 'constraint' => 10, 'unsigned' => true, 'null' => true),
            ), 'order_date');

            // all existing orders should be considered "complete"
            ee()->db->query('UPDATE ' . ee()->db->protect_identifiers('store_orders', true) . '
                SET order_completed_date = order_date');
        }

        // order_status and order_status_updated are now allowed to be NULL (for incomplete orders)
        ee()->dbforge->modify_column('store_orders', array(
            'order_status' => array('name' => 'order_status', 'type' => 'varchar', 'constraint' => 20, 'null' => true),
            'order_status_updated' => array('name' => 'order_status_updated', 'type' => 'int', 'constraint' => 10, 'unsigned' => true, 'null' => true),
        ));

        // new payment and shipping method tables
        if (!ee()->db->table_exists('store_payment_methods')) {
            // payment methods table
            ee()->dbforge->add_field(array(
                'payment_method_id' => array('type' => 'int', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true),
                'site_id' => array('type' => 'int', 'constraint' => 5, 'null' => false),
                'class' => array('type' => 'varchar', 'constraint' => 50, 'null' => false),
                'name' => array('type' => 'varchar', 'constraint' => 50, 'null' => false),
                'title' => array('type' => 'varchar', 'constraint' => 255),
                'settings' => array('type' => 'text'),
                'enabled' => array('type' => 'tinyint', 'constraint' => '1', 'null' => false),
            ));

            ee()->dbforge->add_key('payment_method_id', true);
            ee()->dbforge->create_table('store_payment_methods');
            Update::create_index('store_payment_methods', array('site_id', 'name'), true);

            // migrate data
            ee()->db->query('
                INSERT INTO ' . ee()->db->protect_identifiers('store_payment_methods', true) . '
                    (`payment_method_id`, `site_id`, `class`, `name`, `settings`, `enabled`)
                SELECT `plugin_instance_id`,
                    `site_id`,
                    CONCAT("Merchant_", `plugin_name`),
                    `plugin_name`,
                    `settings`,
                    (CASE `enabled` WHEN "y" THEN 1 ELSE 0 END)
                FROM ' . ee()->db->protect_identifiers('store_plugins', true) . '
                WHERE `plugin_type` = "p"');
        }

        if (!ee()->db->table_exists('store_shipping_methods')) {
            // shipping methods table
            ee()->dbforge->add_field(array(
                'shipping_method_id' => array('type' => 'int', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true),
                'site_id' => array('type' => 'int', 'constraint' => 5, 'null' => false),
                'class' => array('type' => 'varchar', 'constraint' => 50, 'null' => false),
                'title' => array('type' => 'varchar', 'constraint' => 255),
                'settings' => array('type' => 'text'),
                'enabled' => array('type' => 'tinyint', 'constraint' => '1', 'null' => false),
                'display_order' => array('type' => 'int', 'constraint' => 4, 'unsigned' => true, 'null' => false),
            ));

            ee()->dbforge->add_key('shipping_method_id', true);
            ee()->dbforge->create_table('store_shipping_methods');
            Update::create_index('store_shipping_methods', 'site_id');

            // migrate data
            ee()->db->query('
                INSERT INTO ' . ee()->db->protect_identifiers('store_shipping_methods', true) . '
                    (`shipping_method_id`, `site_id`, `class`, `title`, `settings`, `enabled`, `display_order`)
                SELECT `plugin_instance_id`,
                    `site_id`,
                    CONCAT("Store_shipping_", `plugin_name`),
                    `instance_title`,
                    `settings`,
                    (CASE `enabled` WHEN "y" THEN 1 ELSE 0 END),
                    `display_order`
                FROM ' . ee()->db->protect_identifiers('store_plugins', true) . '
                WHERE `plugin_type` = "s"');
        }

        // remove old plugins table
        ee()->dbforge->drop_table('store_plugins');

        // rename plugin_instance_id foreign key to shipping_method_id
        if (ee()->db->field_exists('plugin_instance_id', 'store_shipping_rules')) {
            ee()->dbforge->modify_column('store_shipping_rules', array(
                'plugin_instance_id' => array('name' => 'shipping_method_id', 'type' => 'int', 'constraint' => 10, 'unsigned' => true, 'null' => false),
            ));
        }

        // add tax_shipping to store_tax_rates
        if (!ee()->db->field_exists('tax_shipping', 'store_tax_rates')) {
            ee()->dbforge->add_column('store_tax_rates', array(
                'tax_shipping' => array('type' => 'tinyint', 'constraint' => 1, 'null' => false),
            ), 'tax_rate');

            // existing tax rates keep current behaviour (shipping is taxable)
            ee()->db->update('store_tax_rates', array('tax_shipping' => 1));
        }

        // modify store_tax_rates enabled column to type TINYINT(1)
        ee()->db->where('enabled', 'y')->update('store_tax_rates', array('enabled' => 1));
        ee()->db->where('enabled', 'n')->update('store_tax_rates', array('enabled' => 0));

        ee()->dbforge->modify_column('store_tax_rates', array(
            'enabled' => array('name' => 'enabled', 'type' => 'tinyint', 'constraint' => 1, 'null' => false, 'default' => 0),
        ));

        // add shipping rule defaults
        ee()->db->where('enabled', 'y')->update('store_shipping_rules', array('enabled' => 1));
        ee()->db->where('enabled', 'n')->update('store_shipping_rules', array('enabled' => 0));

        ee()->dbforge->modify_column('store_shipping_rules', array(
            'title' => array('name' => 'title', 'type' => 'varchar', 'constraint' => 50, 'null' => false, 'default' => ''),
            'country_code' => array('name' => 'country_code', 'type' => 'char', 'constraint' => 2, 'null' => false, 'default' => ''),
            'region_code' => array('name' => 'region_code', 'type' => 'varchar', 'constraint' => 5, 'null' => false, 'default' => ''),
            'postcode' => array('name' => 'postcode', 'type' => 'varchar', 'constraint' => 10, 'null' => false, 'default' => ''),
            'base_rate' => array('name' => 'base_rate', 'type' => 'decimal', 'constraint' => '19,4', 'null' => false, 'default' => 0),
            'per_item_rate' => array('name' => 'per_item_rate', 'type' => 'decimal', 'constraint' => '19,4', 'null' => false, 'default' => 0),
            'per_weight_rate' => array('name' => 'per_weight_rate', 'type' => 'decimal', 'constraint' => '19,4', 'null' => false, 'default' => 0),
            'percent_rate' => array('name' => 'percent_rate', 'type' => 'double', 'null' => false, 'default' => 0),
            'min_rate' => array('name' => 'min_rate', 'type' => 'decimal', 'constraint' => '19,4', 'null' => false, 'default' => 0),
            'max_rate' => array('name' => 'max_rate', 'type' => 'decimal', 'constraint' => '19,4', 'null' => false, 'default' => 0),
            'priority' => array('name' => 'priority', 'type' => 'int', 'constraint' => 4, 'unsigned' => true, 'null' => false, 'default' => 0),
            'enabled' => array('name' => 'enabled', 'type' => 'tinyint', 'constraint' => 1, 'null' => false, 'default' => 0),
        ));

        // add order defaults
        ee()->dbforge->modify_column('store_orders', array(
            'order_status_member' => array('name' => 'order_status_member', 'type' => 'int', 'constraint' => 10, 'unsigned' => true, 'null' => false, 'default' => 0),
        ));

        ee()->dbforge->modify_column('store_order_history', array(
            'order_status_updated' => array('name' => 'order_status_updated', 'type' => 'int', 'constraint' => 10, 'unsigned' => true, 'null' => false, 'default' => 0),
            'order_status_member' => array('name' => 'order_status_member', 'type' => 'int', 'constraint' => 10, 'unsigned' => true, 'null' => false, 'default' => 0),
        ));

        // remove unused enabled column from store stock
        Update::drop_column_if_exists('store_stock', 'enabled');
    }
}
