<?php

use BoldMinded\Bloqs\Library\Basee\Update\AbstractUpdate;

class Update_4_03_00 extends AbstractUpdate
{
    public function doUpdate()
    {
        $db = ee('db');

        if (!$db->field_exists('preview_icon', 'blocks_blockdefinition')) {
            $db->query("ALTER TABLE `" . ee()->db->dbprefix . "blocks_blockdefinition` ADD `preview_icon` text DEFAULT NULL AFTER `preview_image`");
            $db->query("ALTER TABLE `" . ee()->db->dbprefix . "blocks_block` MODIFY COLUMN `order` INT(11) NOT NULL DEFAULT 0");
        }

        if (!$db->field_exists('group_id', 'blocks_blockdefinition')) {
            $db->query("ALTER TABLE `" . ee()->db->dbprefix . "blocks_blockdefinition` ADD `group_id` INT(11) NOT NULL DEFAULT 0 AFTER `id`");
        }

        $tablePrefix = ee()->db->dbprefix;
        $groupTable = [
            'name' => $tablePrefix.'blocks_blockgroup',
            'definition' => "CREATE TABLE `{$tablePrefix}blocks_blockgroup` (
                `id` int(20) NOT NULL AUTO_INCREMENT,
                `order` int(11) NOT NULL DEFAULT 0,
                `name` text NOT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;"
        ];

        if (!ee()->db->table_exists($groupTable['name'])) {
            ee()->db->query($groupTable['definition']);
        }
    }
}
