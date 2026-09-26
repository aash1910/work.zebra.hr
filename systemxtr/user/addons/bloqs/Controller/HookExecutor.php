<?php

/**
 * @package     ExpressionEngine
 * @subpackage  Extensions
 * @category    Bloqs
 * @author      Brian Litzinger
 * @copyright   Copyright (c) 2012, 2024 - BoldMinded, LLC
 * @link        http://boldminded.com/add-ons/bloqs
 * @license
 *
 * Copyright (c) 2019. BoldMinded, LLC
 * All rights reserved.
 *
 * This source is commercial software. Use of this software requires a
 * site license for each domain it is used on. Use of this software or any
 * of its source code without express written permission in the form of
 * a purchased commercial or other license is prohibited.
 *
 * THIS CODE AND INFORMATION ARE PROVIDED "AS IS" WITHOUT WARRANTY OF ANY
 * KIND, EITHER EXPRESSED OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND/OR FITNESS FOR A
 * PARTICULAR PURPOSE.
 *
 * As part of the license agreement for this software, all modifications
 * to this source must be submitted to the original author for review and
 * possible inclusion in future releases. No compensation will be provided
 * for patches, although where possible we will attribute each contribution
 * in file revision notes. Submitting such modifications constitutes
 * assignment of copyright to the original author (Brian Litzinger and
 * BoldMinded, LLC) for such modifications. If you do not wish to assign
 * copyright to the original author, your license to  use and modify this
 * source is null and void. Use of this software constitutes your agreement
 * to this clause.
 */

namespace BoldMinded\Bloqs\Controller;

use BoldMinded\Bloqs\Library\Basee\App;
use BoldMinded\Bloqs\Entity\AtomDefinition;
use BoldMinded\Bloqs\Entity\Block;
use BoldMinded\Bloqs\Entity\BlockDefinition;

class HookExecutor
{
    private $EE;

    const CREATE_BLOCK = 'blocks_create_block';
    const DELETE_BLOCK = 'blocks_delete_block';
    const DELETE_BLOCKS_BY_ENTRY = 'blocks_delete_blocks_by_entry';
    const DISCOVER_FIELDTYPES = 'blocks_discover_fieldtypes';
    const DISPLAY_ATOM = 'blocks_display_atom';
    const BEFORE_DISPLAY_FIELD = 'blocks_before_display_field';
    const AFTER_DISPLAY_FIELD = 'blocks_after_display_field';
    const DISASSOCIATE = 'blocks_disassociate';
    const GET_BLOCKS = 'blocks_get_blocks';
    const GET_BLOCK_DEFINITION_USAGE = 'blocks_get_block_definition_usage';
    const HIDE_BLOCK = 'blocks_hide_block';
    const PRE_SAVE_BLOCKS = 'blocks_pre_save_blocks';
    const POST_SAVE_BLOCK = 'blocks_post_save_block';
    const POST_SAVE = 'blocks_post_save';
    const SECRET_FORM_KEY = 'blocks_secret_form_key';
    const SET_ATOM_DATA = 'blocks_set_atom_data';
    const UPDATE_FIELD_DATA = 'blocks_update_field_data';
    const UPDATE_SEARCH_VALUES = 'blocks_update_search_values';
    const SET_BLOCK_ORDER = 'blocks_set_block_order';

    /**
     * Create the hook executor
     *
     * @param object $ee The ExpressionEngine instance.
     */
    public function __construct($ee)
    {
        $this->EE = $ee;
    }

    /**
     * @param $name
     * @return bool
     */
    public function isActive($name) {
        return $this->EE->extensions->active_hook($name);
    }

    /**
     * @param string    $queryString
     * @param array     $queryReplacements
     * @param int       $entryId
     * @param int       $fieldId
     * @param array     $fieldData
     * @return int|null
     */
    public function createBlock($queryString, array $queryReplacements, $entryId, $fieldId, $fieldData = [])
    {
        if ($this->isActive(self::CREATE_BLOCK)) {
            return $this->EE->extensions->call(self::CREATE_BLOCK, $queryString, $queryReplacements, $entryId, $fieldId, $fieldData);
        }

        return null;
    }

    /**
     * @param array $queries
     * @param array $queryReplacements
     */
    public function deleteBlock(array $queries, array $queryReplacements = [])
    {
        if ($this->isActive(self::DELETE_BLOCK)) {
            $this->EE->extensions->call(self::DELETE_BLOCK, $queries, $queryReplacements);
        }
    }

    /**
     * @param int $entryId
     */
    public function deleteBlocksByEntry($entryId)
    {
        if ($this->isActive(self::DELETE_BLOCKS_BY_ENTRY)) {
            $this->EE->extensions->call(self::DELETE_BLOCKS_BY_ENTRY, $entryId);
        }
    }

    /**
     * @param array $queries
     * @param array $queryReplacements
     */
    public function disassociate(array $queries, array $queryReplacements = [])
    {
        if ($this->isActive(self::DISASSOCIATE)) {
            $this->EE->extensions->call(self::DISASSOCIATE, $queries, $queryReplacements);
        }
    }

    /**
     * @param array $fieldtypeArray
     * @return array
     */
    public function discoverFieldtypes(array $fieldtypeArray = [])
    {
        if ($this->isActive(self::DISCOVER_FIELDTYPES)) {
            $fieldtypeArray = $this->EE->extensions->call(self::DISCOVER_FIELDTYPES, $fieldtypeArray);
        }

        return $fieldtypeArray;
    }

    /**
     * @param int $entryId
     * @param BlockDefinition $blockDefinition
     * @param AtomDefinition $atomDefinition
     * @param array $control
     * @return string
     */
    public function displayAtom($entryId, BlockDefinition $blockDefinition, AtomDefinition $atomDefinition, array $control = [])
    {
        if ($this->isActive(self::DISPLAY_ATOM)) {
            $data = $this->EE->extensions->call(self::DISPLAY_ATOM, $entryId, $blockDefinition, $atomDefinition, $control);
        }

        return $data;
    }

    /**
     * @param int|null  $entryId
     * @param int       $fieldId
     * @param array     $viewData
     * @return array
     */
    public function beforeDisplayField($entryId, int $fieldId, array $viewData = []): array
    {
        if ($this->isActive(self::BEFORE_DISPLAY_FIELD)) {
            $viewData = $this->EE->extensions->call(self::BEFORE_DISPLAY_FIELD, $entryId, $fieldId, $viewData);
        }

        return $viewData;
    }

    /**
     * @param int|null  $entryId
     * @param int       $fieldId
     * @param array     $viewData
     * @return array
     */
    public function afterDisplayField($entryId, int $fieldId, array $viewData = []): array
    {
        if ($this->isActive(self::AFTER_DISPLAY_FIELD)) {
            $viewData = $this->EE->extensions->call(self::AFTER_DISPLAY_FIELD, $entryId, $fieldId, $viewData);
        }

        return $viewData;
    }

    /**
     * @param string $queryString
     * @param array $queryReplacements
     * @return \CI_DB_result|null
     */
    public function getBlocks($queryString, array $queryReplacements = [])
    {
        if ($this->isActive(self::GET_BLOCKS)) {
            return $this->EE->extensions->call(self::GET_BLOCKS, $queryString, $queryReplacements);
        }

        return null;
    }

    /**
     * @param Block $block
     * @return bool
     */
    public function hideBlock(Block $block)
    {
        if ($this->isActive(self::HIDE_BLOCK)) {
            return $this->EE->extensions->call(self::HIDE_BLOCK, $block);
        }

        return null;
    }

    /**
     * @param string $queryString
     * @return \CI_DB_result|null
     */
    public function getBlockDefinitionUsage($queryString)
    {
        if ($this->isActive(self::GET_BLOCK_DEFINITION_USAGE)) {
            return $this->EE->extensions->call(self::GET_BLOCK_DEFINITION_USAGE, $queryString);
        }

        return null;
    }

    /**
     * @param array $blocks
     * @param array $context
     */
    public function postSave(array $blocks = [], array $context = [])
    {
        if ($this->isActive(self::POST_SAVE)) {
            $this->EE->extensions->call(self::POST_SAVE, $blocks, $context);

            // If full save was successful then remove the log item
            $this->EE->db->delete('blocks_log', [
                'entry_id' => $context['entry_id'],
                'field_id' => $context['field_id'],
            ]);
        }
    }

    /**
     * @param int $entryId
     * @param int $fieldId
     * @param int $blockId
     * @param array $blockData
     * @return array
     */
    public function postSaveBlock($entryId, $fieldId, $blockId, array $blockData)
    {
        if ($this->isActive(self::POST_SAVE_BLOCK)) {
            $blockData = $this->EE->extensions->call(self::POST_SAVE_BLOCK, $entryId, $fieldId, $blockId, $blockData);
        }

        return $blockData;
    }

    /**
     * @param int $entryId
     * @param int $fieldId
     * @param array $data
     * @return array
     */
    public function preSaveBlocks($entryId, $fieldId, array $data = [])
    {
        if ($this->isActive(self::PRE_SAVE_BLOCKS)) {
            $this->EE->db->insert('blocks_log', [
                'entry_id' => $entryId,
                'field_id' => $fieldId,
                'member_id' => App::userData('member_id'),
                'updated_at' => $this->EE->localize->now,
            ]);

            $data = $this->EE->extensions->call(self::PRE_SAVE_BLOCKS, $entryId, $fieldId, $data);
        }

        return $data;
    }

    /**
     * @param string $keyName
     * @return string
     */
    public function secretFormKey(string $keyName = '')
    {
        if ($this->isActive(self::SECRET_FORM_KEY)) {
            $keyName = $this->EE->extensions->call(self::SECRET_FORM_KEY, $keyName);
        }

        return $keyName;
    }

    /**
     * @param string $queryString
     * @param array  $queryReplacements
     */
    public function setAtomData($queryString, array $queryReplacements = [])
    {
        if ($this->isActive(self::SET_ATOM_DATA)) {
            $this->EE->extensions->call(self::SET_ATOM_DATA, $queryString, $queryReplacements);
        }
    }

    /**
     * @param string $queryString
     * @param array  $queryReplacements
     * @param int    $entryId
     * @param int    $fieldId
     * @param array  $fieldData
     * @return int|null
     */
    public function setBlockOrder($queryString, array $queryReplacements, $entryId, $fieldId, $fieldData = [])
    {
        if ($this->isActive(self::SET_BLOCK_ORDER)) {
            return $this->EE->extensions->call(self::SET_BLOCK_ORDER, $queryString, $queryReplacements, $entryId, $fieldId, $fieldData);
        }

        return null;
    }

    /**
     * @param int   $entryId
     * @param int   $fieldId
     * @param mixed $data
     */
    public function updateFieldData($entryId, $fieldId, $data)
    {
        if ($this->isActive(self::UPDATE_FIELD_DATA)) {
            $this->EE->extensions->call(self::UPDATE_FIELD_DATA, $entryId, $fieldId, $data);
        }
    }

    /**
     * @param array $searchValues
     */
    public function updateSearchValues(array $searchValues = [])
    {
        if ($this->isActive(self::UPDATE_SEARCH_VALUES)) {
            $this->EE->extensions->call(self::UPDATE_SEARCH_VALUES, $searchValues);
        }
    }
}
