<?php

use BoldMinded\Bloqs\Database\Adapter;
use BoldMinded\Bloqs\Library\Basee\Update\AbstractUpdate;
use BoldMinded\Bloqs\Entity\AtomDefinition;
use BoldMinded\Bloqs\Entity\BlockDefinition;

class Update_4_08_00 extends AbstractUpdate
{
    public function doUpdate()
    {
        ee()->load->library('smartforge');
        // Clear out the table cache, otherwise upgrading from a version that does not have the exp_blocks_templates
        // table will cause the modify column query below to fail. This happens when upgrading from a version of Bloqs
        // prior to 4.6 when the blocks_template table was introduced. The table is created during the upgrade, but
        // the cache is stale.
        ee()->db->data_cache = [];

        // drop is_hidden column from block definition table
        ee()->smartforge->drop_column('blocks_blockdefinition', 'is_hidden');

        // add is_editable column to block definition table
        // add is_component column to block definition table
        ee()->smartforge->add_column('blocks_blockdefinition', [
            'is_editable' => [
                'type' => 'int',
                'constraint' => '1',
                'null' => false,
                'default' => 0,
            ],
            'is_component' => [
                'type' => 'int',
                'constraint' => '1',
                'null' => false,
                'default' => 0,
            ],
        ]);

        ee()->smartforge->modify_column('blocks_templates', [
            'templatedefinition_id' => [
                'name' => 'componentdefinition_id',
                'type' => 'int',
                'constraint' => '11',
                'null' => false,
            ],
        ]);

        ee()->smartforge->modify_column('blocks_block', [
            'templatedefinition_id' => [
                'name' => 'componentdefinition_id',
                'type' => 'int',
                'constraint' => '11',
                'null' => false,
            ]
        ]);

        // Now rename the table
        ee()->smartforge->rename_table('blocks_templates', 'blocks_components');

        $adapter = new Adapter(ee());

        $oldWrapperDefinitionId = ee('db')
            ->where('shortname', '__template_wrapper')
            ->get('blocks_blockdefinition')
            ->row('id');

        // Remove the old wrapper as a usable block
        ee('db')
            ->where('blockdefinition_id', $oldWrapperDefinitionId)
            ->delete('blocks_blockfieldusage');

        // Delete the original atom assigned to the single template definition
        // We'll be adding copies of this below for each new block definition with the same name
        ee('db')
            ->where('shortname', '__template_wrapper_atom')
            ->delete('blocks_atomdefinition');

        // Query directly b/c the old component queries will not be in the Adapter anymore
        $componentDefinitions = ee('db')->get('blocks_templatedefinition');

        // Iterate existing components and create new block definitions for them with the default settings
        foreach ($componentDefinitions->result() as $componentDefinition) {
            $blockDefinition = (new BlockDefinition())
                ->setGroupId(0)
                ->setName($componentDefinition->name)
                ->setShortName($componentDefinition->shortname)
                ->setInstructions($componentDefinition->instructions)
                ->setIsComponent(1)
                ->setIsEditable($componentDefinition->is_editable)
                ->setPreviewIcon($componentDefinition->preview_icon)
                ->setPreviewImage($componentDefinition->preview_image)
                ->setSettings((object)[
                    'nesting' => [
                        'root' => 'root_only',
                        'no_children' => 'n',
                        'child_of' => [],
                        'exact_children' => 0,
                        'min_children' => 0,
                        'max_children' => 0,
                    ]
                ]);

            $adapter->createBlockDefinition($blockDefinition);
            $newBlockDefinitionId = (int)$blockDefinition->getId();
            $oldComponentDefinitionId = $componentDefinition->id;

            // Reassign component field usage to block field usage
            $componentFieldUsages = ee('db')
                ->where('id', $oldComponentDefinitionId)
                ->get('blocks_templatefieldusage');

            foreach ($componentFieldUsages->result() as $componentFieldUsage) {
                $adapter->associateBlockDefinitionWithField(
                    $componentFieldUsage->field_id,
                    $newBlockDefinitionId,
                    $componentFieldUsage->order
                );
            }

            // Make sure child blocks are assigned correctly
            ee('db')
                ->where('componentdefinition_id', $oldComponentDefinitionId)
                ->update('blocks_components', [
                    'componentdefinition_id' => $newBlockDefinitionId
                ]);

            ee('db')
                ->where([
                    'blockdefinition_id' => $oldWrapperDefinitionId,
                    'componentdefinition_id' => $newBlockDefinitionId,
                ])
                ->update('blocks_components', [
                    'blockdefinition_id' => $newBlockDefinitionId
                ]);

            // Update blocks_block set blockdefinition_id to $newBlockDefinitionId where $oldComponentDefinitionId
            ee('db')
                ->where([
                    'blockdefinition_id' => $oldWrapperDefinitionId,
                    'componentdefinition_id' => $oldComponentDefinitionId,
                ])
                ->update('blocks_block', [
                    'blockdefinition_id' => $newBlockDefinitionId
                ]);

            // Update blocks_block set componentdefinition_id to $newBlockDefinitionId where $oldComponentDefinitionId
            ee('db')
                ->where('componentdefinition_id', $oldComponentDefinitionId)
                ->update('blocks_block', [
                    'componentdefinition_id' => $newBlockDefinitionId
                ]);

            // Insert a new copy of the old atom for each block since we don't have a single wrapper anymore.
            // This will be hidden by css until edited because of the name.
            $atomDefinition = (new AtomDefinition())
                ->setName('Hidden Atom')
                ->setShortName('__hidden')
                ->setInstructions('This is a legacy atom and will remain hidden as long as the short name is "__hidden". If you choose to use this field you can rename it.')
                ->setOrder(1)
                ->setType('text')
                ->setSettings((object)[
                    'field_maxl' => '1',
                    'field_fmt' => 'none',
                    'field_text_direction' => 'ltr',
                    'field_content_type' => 'all',
                    'col_required' => 'n',
                    'col_search' => 'n',
                ]);

            $adapter->createAtomDefinition($newBlockDefinitionId, $atomDefinition);

            // We created 1 or more new atom definitions that are basically copies of each other
            // now insert some data so the new component block has an atom assigned to it, otherwise
            // the getBlocks query will not find any data associated to the newly created component block,
            // thus not displaying it in an entry.
            $blocks = ee('db')
                ->where('blockdefinition_id', $newBlockDefinitionId)
                ->get('blocks_block');

            foreach ($blocks->result() as $block) {
                $adapter->setAtomData($block->id, $atomDefinition->getId(), '');
            }
        }

        // Drop all keys, rows, then tables
        $this->dropKeys('fk_blocks_templatefieldusage_templatedefinition', 'blocks_templatefieldusage');
        ee('db')->truncate('blocks_templatedefinition');
        ee('db')->truncate('blocks_templatefieldusage');
        ee()->smartforge->drop_table('blocks_templatedefinition');
        ee()->smartforge->drop_table('blocks_templatefieldusage');

        // delete __component_wrapper from block definitions, but don't delete __component_wrapper_atom
        ee('db')
            ->where('shortname', '__template_wrapper')
            ->delete('blocks_blockdefinition');

        ee('db')
            ->where('shortname', '__template_wrapper_atom')
            ->update('blocks_blockdefinition', [
                'instructions' => 'This is a legacy atom and will remain hidden as long as the short name remains "__template_wrapper_atom". If you choose to use this field you can rename it, and change it\'s type."',
            ]);
    }

    /**
     * @param string $keyName
     * @param string $tableName
     */
    private function dropKeys(string $keyName = '', string $tableName = '')
    {
        $dbName = ee('db')->database;
        $tableName = ee('db')->dbprefix . $tableName;

        $result = ee('db')->query("SELECT *
            FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
            WHERE CONSTRAINT_TYPE = 'FOREIGN KEY'
                AND TABLE_SCHEMA = '$dbName'
                AND CONSTRAINT_NAME = '$keyName'");

        if ($result->num_rows()) {
            ee('db')->query("ALTER TABLE $tableName DROP FOREIGN KEY $keyName");
        }
    }
}
