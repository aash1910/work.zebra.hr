<?php

use BoldMinded\Bloqs\Library\Basee\Update\AbstractUpdate;

class Update_4_04_00 extends AbstractUpdate
{
    public function doUpdate()
    {
        $db = ee('db');

        if (!$db->field_exists('deprecated', 'blocks_blockdefinition')) {
            $db->query("ALTER TABLE `" . ee()->db->dbprefix . "blocks_blockdefinition` ADD `deprecated` INT(1) NOT NULL DEFAULT 0 AFTER `preview_icon`");
            $db->query("ALTER TABLE `" . ee()->db->dbprefix . "blocks_blockdefinition` ADD `deprecated_note` text DEFAULT NULL AFTER `deprecated`");
        }
    }
}
