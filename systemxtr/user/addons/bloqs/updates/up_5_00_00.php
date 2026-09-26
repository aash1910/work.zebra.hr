<?php

use BoldMinded\Bloqs\Library\Basee\Update\AbstractUpdate;

class Update_5_00_00 extends AbstractUpdate
{
    public function doUpdate()
    {
        ee()->load->library('smartforge');
        ee()->db->data_cache = [];

        ee()->smartforge->add_column('blocks_components', [
            'cloneable' => [
                'type' => 'int',
                'constraint' => '1',
                'null' => false,
                'default' => 0,
            ],
        ]);

        ee()->smartforge->add_column('blocks_block', [
            'cloneable' => [
                'type' => 'int',
                'constraint' => '1',
                'null' => false,
                'default' => 0,
            ],
        ]);
    }
}
