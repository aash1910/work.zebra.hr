<?php

/*
 * Exp:resso Store module for ExpressionEngine
 * Copyright (c) 2010-2019 Exp:resso (support@exp-resso.com)
 */

namespace Store\Update;

class Update114
{
    /**
     * Add new order variables
     */
    public function up()
    {
        $this->EE = get_instance();

        // add order_handling to store_orders
        if (!ee()->db->field_exists('order_handling', 'store_orders')) {
            ee()->dbforge->add_column('store_orders', array(
                'order_handling_tax' => array('type' => 'decimal', 'constraint' => '19,4', 'null' => false),
                'order_handling' => array('type' => 'decimal', 'constraint' => '19,4', 'null' => false),
            ), 'order_shipping_tax');
        }

        // add shipping_method_rule to store_orders
        if (!ee()->db->field_exists('shipping_method_rule', 'store_orders')) {
            ee()->dbforge->add_column('store_orders', array(
                'shipping_method_rule' => array('type' => 'varchar', 'constraint' => 50),
            ), 'shipping_method_plugin');
        }

        // add weight_units and dimension_units to store_orders
        if (!ee()->db->field_exists('dimension_units', 'store_orders')) {
            ee()->dbforge->add_column('store_orders', array(
                'weight_units' => array('type' => 'varchar', 'constraint' => 5, 'null' => false),
                'order_weight' => array('type' => 'double', 'null' => false),
                'dimension_units' => array('type' => 'varchar', 'constraint' => 5, 'null' => false),
                'order_height' => array('type' => 'double', 'null' => false),
                'order_width' => array('type' => 'double', 'null' => false),
                'order_length' => array('type' => 'double', 'null' => false),
            ), 'tax_rate');
        }

        // add dimensions to store_order_items
        if (!ee()->db->field_exists('weight', 'store_order_items')) {
            ee()->dbforge->add_column('store_order_items', array(
                'tax_exempt' => array('type' => 'char', 'constraint' => 1, 'null' => false),
                'free_shipping' => array('type' => 'char', 'constraint' => 1, 'null' => false),
                'handling_tax' => array('type' => 'decimal', 'constraint' => '19,4', 'null' => false),
                'handling' => array('type' => 'decimal', 'constraint' => '19,4', 'null' => false),
                'height' => array('type' => 'double'),
                'width' => array('type' => 'double'),
                'length' => array('type' => 'double'),
                'weight' => array('type' => 'double'),
            ), 'on_sale');
        }

        // add tax_exempt to store_products
        if (!ee()->db->field_exists('tax_exempt', 'store_products')) {
            ee()->dbforge->add_column('store_products', array(
                'tax_exempt' => array('type' => 'char', 'constraint' => 1, 'null' => false),
            ), 'free_shipping');
        }
    }
}
