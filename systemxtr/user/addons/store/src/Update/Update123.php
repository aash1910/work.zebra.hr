<?php

/*
 * Exp:resso Store module for ExpressionEngine
 * Copyright (c) 2010-2019 Exp:resso (support@exp-resso.com)
 */

namespace Store\Update;

class Update123
{
    /**
     * Add missing promo codes to orders table
     */
    public function up()
    {
        $this->EE = get_instance();

        $sql = 'UPDATE ' . ee()->db->protect_identifiers('store_orders', true) . ' o
            JOIN ' . ee()->db->protect_identifiers('store_promo_codes', true) . ' p
            ON p.promo_code_id = o.promo_code_id
            SET o.promo_code = p.promo_code
            WHERE o.promo_code IS NULL';
        ee()->db->query($sql);
    }
}
