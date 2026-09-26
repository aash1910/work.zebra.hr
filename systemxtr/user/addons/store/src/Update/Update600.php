<?php

namespace Store\Update;

use Store\Update;

class Update600
{
    public function up()
    {
        $sql = "ALTER TABLE exp_store_orders MODIFY COLUMN tax_override TEXT DEFAULT NULL";
        ee()->db->query($sql);
    }
}
