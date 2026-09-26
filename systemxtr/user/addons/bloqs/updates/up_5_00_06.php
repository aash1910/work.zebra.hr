<?php

use BoldMinded\Bloqs\Library\Basee\Update\AbstractUpdate;

class Update_5_00_06 extends AbstractUpdate
{
    public function doUpdate()
    {
        $db = ee('db');

        // Required for installation to work. It caches the tables and does not see the newly added tables.
        $db->data_cache = [];

        if (!$db->table_exists('blocks_log')) {
            ee()->load->dbforge();
            ee()->dbforge->add_field([
                'id' => ['type' => 'int', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
                'entry_id' => ['type' => 'int', 'constraint' => 10, 'unsigned' => true],
                'field_id' => ['type' => 'int', 'constraint' => 10, 'unsigned' => true],
                'member_id' => ['type' => 'int', 'constraint' => 10, 'unsigned' => true],
                'updated_at' => ['type' => 'int', 'constraint' => '10', 'null' => true],
            ]);

            ee()->dbforge->add_key('id', true);
            ee()->dbforge->create_table('blocks_log');
        }
    }
}
