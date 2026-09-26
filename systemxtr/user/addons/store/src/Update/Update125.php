<?php

/*
 * Exp:resso Store module for ExpressionEngine
 * Copyright (c) 2010-2019 Exp:resso (support@exp-resso.com)
 */

namespace Store\Update;

use Store\Update;

class Update125
{
    /**
     * Add extra shipping rule columns
     */
    public function up()
    {
        $this->EE = get_instance();

        if (!ee()->db->field_exists('per_weight_rate', 'store_shipping_rules')) {
            ee()->dbforge->add_column('store_shipping_rules', array(
                'per_weight_rate' => array('type' => 'decimal', 'constraint' => '19,4', 'null' => false),
            ), 'per_item_rate');

            ee()->dbforge->add_column('store_shipping_rules', array(
                'max_order_qty' => array('type' => 'int', 'constraint' => 4, 'unsigned' => true),
                'min_order_qty' => array('type' => 'int', 'constraint' => 4, 'unsigned' => true),
                'max_order_total' => array('type' => 'decimal', 'constraint' => '19,4'),
                'min_order_total' => array('type' => 'decimal', 'constraint' => '19,4'),
                'max_weight' => array('type' => 'double'),
                'min_weight' => array('type' => 'double'),
                'postcode' => array('type' => 'varchar', 'constraint' => 10, 'null' => false),
            ), 'region_code');
        }

        ee()->dbforge->modify_column('store_shipping_rules', array(
            'country_code' => array('name' => 'country_code', 'type' => 'char', 'constraint' => 2, 'null' => false),
            'region_code' => array('name' => 'region_code', 'type' => 'varchar', 'constraint' => 5, 'null' => false),
        ));

        ee()->dbforge->modify_column('store_tax_rates', array(
            'country_code' => array('name' => 'country_code', 'type' => 'char', 'constraint' => 2, 'null' => false),
            'region_code' => array('name' => 'region_code', 'type' => 'varchar', 'constraint' => 5, 'null' => false),
        ));

        // update legacy weight units stored in orders table
        ee()->db->where('weight_units', 'lbs')
            ->set('weight_units', 'lb')
            ->update('store_orders');
    }
}
