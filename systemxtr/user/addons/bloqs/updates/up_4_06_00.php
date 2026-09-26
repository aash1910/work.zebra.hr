<?php

use BoldMinded\Bloqs\Database\Adapter;
use BoldMinded\Bloqs\Library\Basee\Update\AbstractUpdate;
use BoldMinded\Bloqs\Entity\AtomDefinition;
use BoldMinded\Bloqs\Entity\BlockDefinition;

class Update_4_06_00 extends AbstractUpdate
{
    public function doUpdate()
    {
        $this->addHooks([
            ['hook'=>'cp_js_end', 'method'=>'cp_js_end'],
        ]);

        $tablePrefix = ee()->db->dbprefix;

        $tables = [
            'tbl_one' => [
                'name' => $tablePrefix.'blocks_templates',
                'definition' => "CREATE TABLE `{$tablePrefix}blocks_templates` (
                    `id` bigint(20) NOT NULL AUTO_INCREMENT,
                    `blockdefinition_id` bigint(20) NOT NULL,
                    `templatedefinition_id` int(11) NOT NULL,
                    `order` int(11) NOT NULL DEFAULT 0,
                    `parent_id` int(11) NOT NULL DEFAULT 0,
                    `depth` int(11) NOT NULL DEFAULT 0,
                    `lft` int(11) NOT NULL DEFAULT 0,
                    `rgt` int(11) NOT NULL DEFAULT 0,
                    PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;"
            ],
            'tbl_two' => [
                'name' => $tablePrefix.'blocks_templatedefinition',
                'definition' => "CREATE TABLE `{$tablePrefix}blocks_templatedefinition` (
                    `id` bigint(20) NOT NULL AUTO_INCREMENT,
                    `name` text NOT NULL,
                    `shortname` text NOT NULL,
                    `instructions` text NOT NULL,
                    `order` int(11) NOT NULL,
                    `preview_image` text DEFAULT NULL,
                    `preview_icon` text DEFAULT NULL,
                    `is_editable` int(1) NOT NULL DEFAULT 0,
                    PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;"
            ],
            'tbl_three' => [
                'name' => $tablePrefix.'blocks_templatefieldusage',
                'definition' => "CREATE TABLE `{$tablePrefix}blocks_templatefieldusage` (
                    `id` int(20) NOT NULL AUTO_INCREMENT,
                    `field_id` int(6) NOT NULL,
                    `templatedefinition_id` bigint(20) NOT NULL,
                    `order` int(11) DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `uk_blocks_templatefieldusage_fieldid_templatedefinitionid` (`field_id`,`templatedefinition_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;"
            ],
        ];

        foreach ($tables as $table) {
            if (!ee()->db->table_exists($table['name'])) {
                ee()->db->query($table['definition']);
            }
        }

        $db = ee('db');

        if (!$db->field_exists('templatedefinition_id', 'blocks_block')) {
            $db->query("ALTER TABLE `" . ee()->db->dbprefix . "blocks_block` ADD `templatedefinition_id` INT(1) NOT NULL DEFAULT 0 AFTER `rgt`");
        }

        // Add our new field to indicate if a definition is hidden. Used for block templates (all templates are a child of this definition)
        if (!$db->field_exists('is_hidden', 'blocks_blockdefinition')) {
            $db->query("ALTER TABLE `" . ee()->db->dbprefix . "blocks_blockdefinition` ADD `is_hidden` INT(1) NOT NULL DEFAULT 0 AFTER `deprecated_note`");

            $definition = (new BlockDefinition())
                ->setGroupId(0)
                ->setIsHidden(1)
                ->setName('Template Wrapper')
                ->setShortName('__template_wrapper')
                ->setInstructions('Do not delete this bloq, it is used as a hidden wrapper for Bloq Templates.')
                ->setSettings((object) [
                    'nesting' => [
                        'root' => 'root_only',
                        'no_children' => 'n',
                        'child_of' => [],
                        'exact_children' => 0,
                        'min_children' => 0,
                        'max_children' => 0,
                    ]
                ]);

            $this->createBlockDefinition($definition);

            $atomDefinition = (new AtomDefinition())
                ->setName('Template Wrapper Atom')
                ->setShortName('__template_wrapper_atom')
                ->setInstructions('Do not delete this atom, it is used in the hidden wrapper for Bloq Templates.')
                ->setOrder(1)
                ->setType('text')
                ->setSettings((object) [
                    'field_maxl' => '1',
                    'field_fmt' => 'none',
                    'field_text_direction' => 'ltr',
                    'field_content_type' => 'all',
                    'col_required' => 'n',
                    'col_search' => 'n',
                ]);

            $this->createAtomDefinition($definition->getId(), $atomDefinition);
        }
    }

    /**
     * These are copies of methods found in the Adapter class, but older copies of them before Components were added.
     * Using these local methods instead of the Adapter versions can help ensure this upgrade step does not fail
     * if there are any future changes to the BlockDefinition, AtomDefinition, or Adapter classes.
     */

    /**
     * @param BlockDefinition $blockDefinition
     */
    private function createBlockDefinition(BlockDefinition $blockDefinition)
    {
        $queryString = <<<EOF
INSERT INTO exp_blocks_blockdefinition
    (`group_id`, `name`, `shortname`, `instructions`, `deprecated`, `deprecated_note`, `preview_image`, `preview_icon`, `settings`)
VALUES
    (:groupId, :name, :shortName, :instructions, :deprecated, :note, :previewImage, :previewIcon, :settings)
EOF;

        $ee = ee();
        $adapter = new Adapter($ee);

        $adapter->query($queryString, [
            'groupId' => $blockDefinition->getGroupId(),
            'name' => $blockDefinition->getName(),
            'shortName' => $blockDefinition->getShortName(),
            'instructions' => $blockDefinition->getInstructions(),
            'deprecated' => $blockDefinition->isDeprecated(),
            'note' => $blockDefinition->getDeprecatedNote(),
            'previewImage' => $blockDefinition->getPreviewImage(),
            'previewIcon' => $blockDefinition->getPreviewIcon(),
            'settings' => json_encode($blockDefinition->getSettings()),
        ]);

        $blockDefinition->id = $ee->db->insert_id();
    }

    /**
     * @param $blockDefinitionId
     * @param $atomDefinition
     */
    private function createAtomDefinition($blockDefinitionId, AtomDefinition $atomDefinition)
    {
        $queryString = <<<EOF
INSERT INTO exp_blocks_atomdefinition
  (`blockdefinition_id`, `shortname`, `name`, `instructions`, `order`, `type`, `settings`)
VALUES
  (:blockDefinitionId, :shortName, :name, :instructions, :order, :type, :settings)
EOF;
        $adapter = new Adapter(ee());

        $adapter->query($queryString, [
            'blockDefinitionId' => $blockDefinitionId,
            'name' => $atomDefinition->getName(),
            'shortName' => $atomDefinition->getShortName(),
            'instructions' => $atomDefinition->getInstructions(),
            'order' => $atomDefinition->getOrder(),
            'type' => $atomDefinition->getType(),
            'settings' => json_encode($atomDefinition->getSettings())
        ]);
    }
}
