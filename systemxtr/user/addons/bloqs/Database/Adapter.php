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

namespace BoldMinded\Bloqs\Database;

use BoldMinded\Bloqs\Library\Basee\App;
use BoldMinded\Bloqs\Entity\BlockGroup;
use CI_DB_result;
use BoldMinded\Bloqs\Controller\HookExecutor;
use BoldMinded\Bloqs\Helper\TreeHelper;
use BoldMinded\Bloqs\Entity\BlockDefinition;
use BoldMinded\Bloqs\Entity\AtomDefinition;
use BoldMinded\Bloqs\Entity\Block;
use BoldMinded\Bloqs\Entity\Atom;
use ExpressionEngine\Legacy\Facade;

class Adapter
{
    private Facade $EE;

    private array $cache;

    private HookExecutor $_hookExecutor;

    private static $postContentId = null;

    /**
     * @throws \Exception
     */
    function __construct(Facade $ee) {
        if (is_null($ee)) {
            throw new \Exception("ExpressionEngine object is required");
        }
        $this->EE = $ee;
        $this->_hookExecutor = new HookExecutor($ee);

        if (!isset($this->EE->session->cache['Bloqs/Adapter'])) {
            $this->EE->session->cache['Bloqs/Adapter'] = [];
        }
        $this->cache =& $this->EE->session->cache['Bloqs/Adapter'];
    }

    /**
     * @param int $entryId
     * @param int $fieldId
     * @return array
     * @see $this->getBlocks()
     */
    public function getContent($entryId, $fieldId)
    {
        return $this->getBlocks($entryId, $fieldId);
    }

    /**
     * @param int $entryId
     * @param int $fieldId
     * @return array
     */
    public function getBlocks($entryId, $fieldId)
    {
        $collection = [];

        $queryString = <<<EOF
SELECT
    bd.id as bd_id,
    bd.group_id as bd_group_id,
    bd.shortname as bd_shortname,
    bd.name as bd_name,
    bd.instructions as bd_instructions,
    bd.deprecated as bd_deprecated,
    bd.deprecated_note as bd_deprecated_note,
    bd.settings as bd_settings,
    bd.is_component as bd_is_component,
    bd.is_editable as bd_is_editable,
    b.id as b_id,
    b.order as b_order,
    b.parent_id as b_parent_id,
    b.draft as b_draft,
    b.depth as b_depth,
    b.lft as b_lft,
    b.rgt as b_rgt,
    b.cloneable as b_cloneable,
    b.componentdefinition_id as b_componentdefinition_id,
    ad.id as ad_id,
    ad.shortname as ad_shortname,
    ad.name as ad_name,
    ad.instructions as ad_instructions,
    ad.order as ad_order,
    ad.type as ad_type,
    ad.settings as ad_settings,
    a.id as a_id,
    IFNULL(a.data, '') as a_data
FROM exp_blocks_block b
LEFT JOIN exp_blocks_blockdefinition bd
  ON b.blockdefinition_id = bd.id
LEFT JOIN exp_blocks_atomdefinition ad
  ON ad.blockdefinition_id = bd.id
LEFT JOIN exp_blocks_atom a
  ON a.block_id = b.id AND a.atomdefinition_id = ad.id
WHERE b.field_id = :fieldId AND entry_id = :entryId
ORDER BY b.order, ad.order
EOF;

        $queryReplacements = [
            'fieldId' => $fieldId,
            'entryId' => $entryId,
        ];

        // -------------------------------------------
        //  'blocks_get_blocks' hook
        //
        if ($this->_hookExecutor->isActive(HookExecutor::GET_BLOCKS)) {
            $query = $this->_hookExecutor->getBlocks($queryString, $queryReplacements);
        } else {
            $query = $this->query($queryString, $queryReplacements);
        }
        //
        // -------------------------------------------

        $currentBlock = NULL;
        $previousBlockId = NULL;

        /** @var \CI_DB_result $query */
        foreach ($query->result() as $row) {
            if ($previousBlockId !== intval($row->b_id)) {
                $previousBlockId = intval($row->b_id);
                if (!is_null($currentBlock)) {
                    $collection[] = $currentBlock;
                }

                $blockDefinition = new BlockDefinition();
                $blockDefinition
                    ->setId(intval($row->bd_id))
                    ->setGroupId(intval($row->bd_group_id))
                    ->setShortName($row->bd_shortname)
                    ->setName($row->bd_name)
                    ->setInstructions($row->bd_instructions ?: '')
                    ->setDeprecated($row->bd_deprecated ?: 0)
                    ->setDeprecatedNote($row->bd_deprecated_note ?: '')
                    ->setSettings(json_decode($row->bd_settings ?: '', true))
                    ->setIsComponent($row->bd_is_component ?: 0)
                    ->setIsEditable($row->bd_is_editable ?: 0)
                    ->setBlocks($this->getBlocksInComponent(intval($row->bd_id)))
                ;

                $currentBlock = new Block();
                $currentBlock
                    ->setId(intval($row->b_id))
                    ->setOrder(intval($row->b_order))
                    ->setDepth(intval($row->b_depth))
                    ->setParentId(intval($row->b_parent_id))
                    ->setDraft(intval($row->b_draft))
                    ->setLft(intval($row->b_lft))
                    ->setRgt(intval($row->b_rgt))
                    ->setCloneable(intval($row->b_cloneable))
                    ->setComponentDefinitionId(intval($row->b_componentdefinition_id))
                    ->setDefinition($blockDefinition)
                ;
            }

            $atomDefinition = new AtomDefinition();
            $atomDefinition
                ->setId(intval($row->ad_id))
                ->setShortName($row->ad_shortname)
                ->setName($row->ad_name)
                ->setInstructions($row->ad_instructions ?: '')
                ->setOrder(intval($row->ad_order))
                ->setType($row->ad_type)
                ->setSettings(json_decode($row->ad_settings ?: '', true))
            ;

            $blockDefinition->addAtomDefinition($atomDefinition->shortname, $atomDefinition);

            $atom = new Atom();
            $atom
                ->setId(intval($row->a_id))
                ->setValue($row->a_data)
                ->setDefinition($atomDefinition)
            ;

            $currentBlock->addAtom($atomDefinition->shortname, $atom);
        }

        if (!is_null($currentBlock)) {
            $collection[] = $currentBlock;
        }

        return $collection;
    }

    /**
     * Used primarily for EE's Live Preview, but given any array of blocks from $_POST data will
     * return a collection of Blocks to be passed to TagController.
     *
     * @param array $blocks
     * @param int $fieldId
     * @param int $contentId
     * @param int $entryId
     * @return array
     */
    public function getBlocksFromPost($blocks, $fieldId, $contentId, $entryId)
    {
        // If it's a LivePreview request and it's not the first time this has been called,
        // meaning it's likely a Bloqs field in an entry in a Relationship field. Since that
        // related entry is already saved and no modifications can be made to it in this
        // Live Preview request, simply fetch everything from the database as if it were
        // a normal front-end template request.
        if (
            $contentId !== null &&
            $contentId !== $entryId &&
            self::$postContentId !== null
        ) {
            return $this->getBlocks($contentId, $fieldId);
        }

        // Record initial entry_id/content_id from the first Live Preview request.
        // Each time this function is called the contentId and blocks will be the same
        // because it's source is the POST data from the entry being live previewed.
        // We need to know if we've been here before during the first request so we can
        // deviate during subsequent requests, likely from Bloqs in entries in a
        // Relationship field.
        self::$postContentId = $contentId;

        $i = 1;
        $collection = [];
        $blockDefinitions = $this->getBlockDefinitions();

        $treeHelper = new TreeHelper();
        $treeOrder = json_decode($blocks['tree_order'] ?: '', true);

        if ($treeOrder) {
            $treeHelper->buildNestedSet($treeOrder);
        }

        $treeData = $treeHelper->getTreeData();

        foreach ($blocks as $blockName => $blockData) {
            // Make sure it is a block, and not the hidden placeholder for new rows
            if (
                substr($blockName, 0, 6) !== 'blocks' ||
                $blockName === 'blocks_new_block_0' ||
                (isset($blockData['deleted']) && $blockData['deleted'] == 'true')
            ) {
                continue;
            }

            $blockDefinitionId = intval($blockData['blockDefinitionId']);
            /** @var BlockDefinition $blockDefinition */
            $blockDefinition = $this->findWhere($blockDefinitions, ['id' => $blockDefinitionId]);
            $atomDefinitions = $blockDefinition->getAtomDefinitions();
            $blockId = isset($blockData['id']) ? intval($blockData['id']) : $blockName;

            $currentBlock = new Block();
            $currentBlock
                ->setId($i)
                ->setOriginalBlockName($blockName)
                ->setOrder($i)
                ->setDefinition($blockDefinition)
                ->setComponentDefinitionId($blockData['componentDefinitionId'] ?? 0)
                ->setCloneable((int) $blockData['cloneable'] ?? 0)
                ->setDraft((int) $blockData['draft'] ?? 0)
                ->setDepth(0)
                ->setParentId(0)
                ->setLft(0)
                ->setRgt(0)
            ;

            if (isset($treeData[$blockId])) {
                // Not entirely sure this is necessary, but it does normalize the data
                // so "correct" parent_ids are used in LivePreview.
                // $parentId = $treeData[$blockId]['parent_id'];
                if ($treeData[$blockId]['parent_id'] === null) {
                    $parentId = 0;
                } else {
                    // Order and ID are basically the same for mock data
                    $parentId = $treeData[$treeData[$blockId]['parent_id']]['order'];
                }

                $currentBlock
                    ->setDepth($treeData[$blockId]['depth'])
                    ->setParentId($parentId)
                    ->setLft($treeData[$blockId]['lft'])
                    ->setRgt($treeData[$blockId]['rgt'])
                ;
            }

            $atomId = 1;

            if (isset($blockData['values'])) {
                foreach ($blockData['values'] as $columnId => $columnValue) {
                    $atomDefinitionId = (int)str_replace('col_id_', '', $columnId);
                    /** @var AtomDefinition $atomDefinition */
                    $atomDefinition = $this->findWhere($atomDefinitions, ['id' => $atomDefinitionId]);

                    // This is kind of hacky, and really not a fan of this, but the assets_live_preview_query hook
                    // gets the real data that we need to display images in LP, but we need a value set on the Atom
                    // in order for that hook to even trigger, and an array of [0 => '1234'] is not valid in Assets case.
                    // If this becomes more of an issue with other add-ons, consider adding a hook here.
                    if (is_array($columnValue) && $atomDefinition->getType() === 'assets') {
                        $columnValue = implode('', array_filter($columnValue));
                    }

                    $atom = new Atom();
                    $atom
                        ->setId($atomId)
                        ->setValue($columnValue)
                        ->setDefinition($atomDefinition)
                    ;

                    $currentBlock->addAtom($atomDefinition->shortname, $atom);

                    $atomId++;
                }
            }

            $collection[] = $currentBlock;

            $i++;
        }

        return $collection;
    }

    /**
     * @param int $entryId
     * @param int $fieldId
     * @return array
     */
    public function getBlockIds($entryId, $fieldId): array
    {
        return array_column($this->getBlocks($entryId, $fieldId), 'id');
    }

    /**
     * Given an array of objects, find the object matching the properties defined.
     * Similar to Underscore.js _where()
     *
     * @param array $list
     * @param array $props
     * @return mixed
     */
    private function where($list, $props)
    {
        return $this->_where($list, $props);
    }

    /**
     * Given an array of objects, find the first object matching the properties defined.
     * Similar to Underscore.js _findWhere()
     *
     * @param array $list
     * @param array $props
     * @return mixed
     */
    private function findWhere($list, $props)
    {
        $result = $this->_where($list, $props);

        return $result[0] ?? null;
    }

    /**
     * @param array $list
     * @param array $props
     * @return mixed
     */
    private function _where($list, $props)
    {
        $result = array_filter(
            $list,
            function ($e) use ($props) {
                $count = 0;
                foreach ($props as $key => $value) {
                    if ($value === $e->$key) {
                        $count += 1;
                    }
                    return $count === count($props);
                }
            }
        );

        return array_values($result);
    }

    /**
     * @return BlockGroup[]
     */
    public function getBlockGroups(): array
    {
        $collection = [];

        $queryString = <<<EOF
SELECT
    bg.id as bg_id,
    bg.name as bg_name,
    bg.order as bg_order
FROM exp_blocks_blockgroup bg
ORDER BY bg.order DESC
EOF;

        $ee = $this->EE;

        /** @var \CI_DB_result $query */
        $query = $ee->db->query($queryString);

        foreach ($query->result() as $row) {
            $blockGroup = new BlockGroup();
            $blockGroup
                ->setId(intval($row->bg_id))
                ->setName($row->bg_name)
                ->setOrder(intval($row->bg_order))
            ;

            $collection[] = $blockGroup;
        }

        return $collection;
    }

    /**
     * @return array
     */
    public function getBlockGroupsDictionary(): array
    {
        $blockGroups = $this->getBlockGroups();
        $blockGroupsDictionary = [0 => 'Ungrouped'];

        foreach ($blockGroups as $blockGroup) {
            $blockGroupsDictionary[$blockGroup->getId()] = $blockGroup->getName();
        }

        return $blockGroupsDictionary;
    }

    /**
     * @param int $blockGroupId
     * @return BlockGroup|null
     */
    public function getBlockGroupById(int $blockGroupId)
    {
        $blockGroups = $this->getBlockGroups();
        $blockGroup = null;

        foreach ($blockGroups as $blockGroupCandidate) {
            if ($blockGroupCandidate->getId() === $blockGroupId) {
                $blockGroup = $blockGroupCandidate;
                break;
            }
        }

        return $blockGroup;
    }

    /**
     * @param int $blockGroupId
     * @return BlockDefinition[]
     */
    public function getBlockDefinitionsByGroupId(int $blockGroupId): array
    {
        return $this->where($this->getBlockDefinitions(), ['groupId' => $blockGroupId]);
    }

    /**
     * @param bool $withHidden
     * @return BlockDefinition[]
     */
    public function getBlockDefinitions(): array
    {
        $cacheKey = __FUNCTION__;

        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $collection = [];

        $queryString = <<<EOF
SELECT
    bd.id as bd_id,
    bd.group_id as bd_group_id,
    bd.shortname as bd_shortname,
    bd.name as bd_name,
    bd.instructions as bd_instructions,
    bd.deprecated as bd_deprecated,
    bd.deprecated_note as bd_deprecated_note,
    bd.preview_image as bd_preview_image,
    bd.preview_icon as bd_preview_icon,
    bd.settings as bd_settings,
    bd.is_component as bd_is_component,
    bd.is_editable as bd_is_editable,
    ad.id as ad_id,
    ad.shortname as ad_shortname,
    ad.name as ad_name,
    ad.instructions as ad_instructions,
    ad.order as ad_order,
    ad.type as ad_type,
    ad.settings as ad_settings
FROM exp_blocks_blockdefinition bd
LEFT JOIN exp_blocks_atomdefinition ad
    ON ad.blockdefinition_id = bd.id
LEFT JOIN exp_blocks_blockgroup bg
    ON bd.group_id = bg.id
ORDER BY bg.order DESC, bd.shortname, ad.order
EOF;

        /** @var \CI_DB_result $query */
        $query = $this->query($queryString);

        foreach ($query->result() as $row) {
            if (!array_key_exists(intval($row->bd_id), $collection)) {
                $currentBlockDefinition = new BlockDefinition();
                $currentBlockDefinition
                    ->setId(intval($row->bd_id))
                    ->setGroupId(intval($row->bd_group_id))
                    ->setShortName($row->bd_shortname)
                    ->setName($row->bd_name)
                    ->setInstructions($row->bd_instructions ?: '')
                    ->setDeprecated($row->bd_deprecated ?: 0)
                    ->setDeprecatedNote($row->bd_deprecated_note ?: '')
                    ->setPreviewImage($row->bd_preview_image ?: '')
                    ->setPreviewIcon($row->bd_preview_icon ?: '')
                    ->setSettings(json_decode($row->bd_settings ?: '', true))
                    ->setIsComponent($row->bd_is_component ?: 0)
                    ->setIsEditable($row->bd_is_editable ?: 0)
                ;

                if ($currentBlockDefinition->isComponent()) {
                    $currentBlockDefinition->setBlocks($this->getBlocksInComponent($currentBlockDefinition->getId()));
                }

                $collection[intval($row->bd_id)] = $currentBlockDefinition;
            } else {
                $currentBlockDefinition = $collection[intval($row->bd_id)];
            }

            $atomDefinition = new AtomDefinition();
            $atomDefinition
                ->setId(intval($row->ad_id))
                ->setShortName($row->ad_shortname)
                ->setName($row->ad_name)
                ->setInstructions($row->ad_instructions ?: '')
                ->setOrder(intval($row->ad_order))
                ->setType($row->ad_type)
                ->setSettings(json_decode($row->ad_settings ?: '', true))
            ;

            $currentBlockDefinition->addAtomDefinition($row->ad_shortname, $atomDefinition);
        }

        // Send back a 0 indexed array
        $collection = array_values($collection);

        $this->cache[$cacheKey] = $collection;

        return $collection;
    }

    /**
     * @param int  $fieldId
     * @return array
     */
    public function getBlockDefinitionsForField(int $fieldId): array
    {
        $cacheKey = __FUNCTION__ . $fieldId;

        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $collection = [];

        $queryString = <<<EOF
SELECT
    bd.id as bd_id,
    bd.group_id as bd_grouo_id,
    bd.shortname as bd_shortname,
    bd.name as bd_name,
    bd.instructions as bd_instructions,
    bd.deprecated as bd_deprecated,
    bd.deprecated_note as bd_deprecated_note,
    bd.preview_image as bd_preview_image,
    bd.preview_icon as bd_preview_icon,
    bd.settings as bd_settings,
    bd.is_component as bd_is_component,
    bd.is_editable as bd_is_editable,
    ad.id as ad_id,
    ad.shortname as ad_shortname,
    ad.name as ad_name,
    ad.instructions as ad_instructions,
    ad.order as ad_order,
    ad.type as ad_type,
    ad.settings as ad_settings
FROM exp_blocks_blockfieldusage bfu
LEFT JOIN exp_blocks_blockdefinition bd
  ON bd.id = bfu.blockdefinition_id
LEFT JOIN exp_blocks_atomdefinition ad
  ON ad.blockdefinition_id = bd.id
LEFT JOIN exp_blocks_blockgroup bg
  ON bd.group_id = bg.id
WHERE bfu.field_id = :fieldId
ORDER BY bg.order DESC, bg.name DESC, bd.group_id DESC, bfu.order, ad.order;
EOF;

        /** @var \CI_DB_result $query */
        $query = $this->query($queryString, [
            'fieldId' => $fieldId,
        ]);

        foreach ($query->result() as $row) {
            if (!array_key_exists(intval($row->bd_id), $collection)) {
                $currentBlockDefinition = new BlockDefinition();
                $currentBlockDefinition
                    ->setId(intval($row->bd_id))
                    ->setGroupId(intval($row->bd_grouo_id))
                    ->setShortName($row->bd_shortname)
                    ->setName($row->bd_name)
                    ->setInstructions($row->bd_instructions ?: '')
                    ->setDeprecated($row->bd_deprecated ?: 0)
                    ->setDeprecatedNote($row->bd_deprecated_note ?: '')
                    ->setPreviewImage($row->bd_preview_image ?: '')
                    ->setPreviewIcon($row->bd_preview_icon ?: '')
                    ->setSettings(json_decode($row->bd_settings ?: '', true))
                    ->setIsComponent($row->bd_is_component ?: 0)
                    ->setIsEditable($row->bd_is_editable ?: 0)
                    ->setBlocks($this->getBlocksInComponent(intval($row->bd_id)))
                ;

                $collection[intval($row->bd_id)] = $currentBlockDefinition;
            } else {
                $currentBlockDefinition = $collection[intval($row->bd_id)];
            }

            $atomDefinition = new AtomDefinition();
            $atomDefinition
                ->setId(intval($row->ad_id))
                ->setShortName($row->ad_shortname)
                ->setName($row->ad_name)
                ->setInstructions($row->ad_instructions ?: '')
                ->setOrder(intval($row->ad_order))
                ->setType($row->ad_type)
                ->setSettings(json_decode($row->ad_settings ?: '', true))
            ;

            $currentBlockDefinition->addAtomDefinition($row->ad_shortname, $atomDefinition);
        }

        // Send back a 0 indexed array
        $collection = array_values($collection);

        $this->cache[$cacheKey] = $collection;

        return $collection;
    }

    /**
     * @return array
     */
    public function getBlockDefinitionsUsage(): array
    {
        $cacheKey = __FUNCTION__;

        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $queryString = <<<EOF
SELECT DISTINCT
    b.blockdefinition_id as 'bd_id',
    b.entry_id as 'entry_id',
    -- pl.long_name as 'language',
    -- b.publisher_status as 'status',
    c.channel_id as 'channel_id',
    c.channel_name as 'channel_name',
    t.title as 'title'
FROM exp_blocks_block AS b
JOIN exp_blocks_blockdefinition AS bd
    ON bd.id = b.blockdefinition_id
LEFT JOIN exp_channel_titles AS t
    ON t.entry_id = b.entry_id
LEFT JOIN exp_channels AS c
    ON c.channel_id = t.channel_id
ORDER BY b.entry_id ASC;
EOF;

        $collection = [];

        // -------------------------------------------
        //  'blocks_get_block_definition_usage' hook
        //
        if ($this->_hookExecutor->isActive(HookExecutor::GET_BLOCK_DEFINITION_USAGE)) {
            /** @var \CI_DB_result $query */
            $query = $this->_hookExecutor->getBlockDefinitionUsage($queryString);
        } else {
            /** @var \CI_DB_result $query */
            $query = $this->query($queryString);
        }
        //
        // -------------------------------------------

        foreach ($query->result() as $row) {
            $collection[$row->bd_id][] = $row->entry_id;
            $collection[$row->bd_id] = array_unique($collection[$row->bd_id]);
        }

        $this->cache[$cacheKey] = $collection;

        return $collection;
    }

    /**
     * @return array
     */
    public function getBlocksUsedInComponents(): array
    {
        $cacheKey = __FUNCTION__;

        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $queryString = <<<EOF
SELECT DISTINCT
    c.blockdefinition_id as 'bd_id',
    c.componentdefinition_id as 'c_id'
FROM exp_blocks_components AS c
JOIN exp_blocks_blockdefinition AS bd
    ON bd.id = c.componentdefinition_id
    AND bd.id != c.blockdefinition_id
EOF;

        $query = $this->query($queryString);

        $collection = [];

        foreach ($query->result() as $row) {
            $collection[$row->bd_id][] = $row->c_id;
            $collection[$row->bd_id] = array_unique($collection[$row->bd_id]);
        }

        $this->cache[$cacheKey] = $collection;

        return $collection;
    }

    /**
     * @param int $blockDefinitionId
     * @return array
     */
    public function getBlocksUsedInComponentByDefinitionId(int $blockDefinitionId): array
    {
        $cacheKey = __FUNCTION__ . $blockDefinitionId;

        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $queryString = <<<EOF
SELECT DISTINCT
    c.blockdefinition_id as 'bd_id',
    c.componentdefinition_id as 'c_id'
FROM exp_blocks_components AS c
JOIN exp_blocks_blockdefinition AS bd
    ON bd.id = c.componentdefinition_id
WHERE c.blockdefinition_id = :blockDefinitionId
AND c.blockdefinition_id != c.componentdefinition_id -- Don't get itself
EOF;
        $query = $this->query($queryString, [
            'blockDefinitionId' => $blockDefinitionId
        ]);

        $collection = [];

        foreach ($query->result() as $row) {
            $collection[$row->bd_id][] = $row->c_id;
            $collection[$row->bd_id] = array_unique($collection[$row->bd_id]);
        }

        $this->cache[$cacheKey] = $collection;

        return $collection;
    }

    /**
     * @param int $fieldId
     * @return array
     */
    public function getBlockDefinitionsUsageByFieldId(int $fieldId): array
    {
        $cacheKey = __FUNCTION__ . $fieldId;

        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $queryString = <<<EOF
SELECT DISTINCT
    b.blockdefinition_id as 'bd_id',
    b.field_id as 'bd_field_id',
    b.entry_id as 'entry_id'
FROM exp_blocks_block AS b
JOIN exp_blocks_blockdefinition bd
    ON bd.id = b.blockdefinition_id
WHERE b.field_id = :fieldId
ORDER BY b.entry_id ASC;
EOF;

        $collection = [];
        $query = $this->query($queryString, [
            'fieldId' => $fieldId
        ]);

        foreach ($query->result() as $row) {
            $collection[$row->bd_id][] = $row->entry_id;
            $collection[$row->bd_id] = array_unique($collection[$row->bd_id]);
        }

        $this->cache[$cacheKey] = $collection;

        return $collection;
    }

    /**
     * @param int  $blockDefinitionId
     * @return BlockDefinition|null
     */
    public function getBlockDefinitionById(int $blockDefinitionId)
    {
        $cacheKey = __FUNCTION__ . $blockDefinitionId;

        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $blockDefinitions = $this->getBlockDefinitions();
        $blockDefinition = null;

        foreach ($blockDefinitions as $blockDefinitionCandidate) {
            if ($blockDefinitionCandidate->getId() === $blockDefinitionId) {
                $blockDefinition = $blockDefinitionCandidate;
                break;
            }
        }

        $this->cache[$cacheKey] = $blockDefinition;

        return $blockDefinition;
    }

    /**
     * @param string $shortName
     * @return BlockDefinition|null
     */
    public function getBlockDefinitionByShortname(string $shortName)
    {
        $cacheKey = __FUNCTION__ . $shortName;

        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $blockDefinitions = $this->getBlockDefinitions();
        $blockDefinition = null;

        foreach ($blockDefinitions as $blockDefinitionCandidate) {
            if ($blockDefinitionCandidate->shortname === $shortName) {
                $blockDefinition = $blockDefinitionCandidate;
                break;
            }
        }

        $this->cache[$cacheKey] = $blockDefinition;

        return $blockDefinition;
    }

    /**
     * @param int $fieldId
     * @param int $blockDefinitionId
     * @param int $order
     */
    public function associateBlockDefinitionWithField(int $fieldId, int $blockDefinitionId, int $order)
    {
        $queryString = <<<EOF
INSERT INTO exp_blocks_blockfieldusage
    (`field_id`, `blockdefinition_id`, `order`)
VALUES
    (:fieldId, :blockDefinitionId, :order)
ON DUPLICATE KEY UPDATE
    `order` = :order
EOF;

        $this->query($queryString, [
            'fieldId' => $fieldId,
            'blockDefinitionId' => $blockDefinitionId,
            'order' => $order
        ]);
    }

    /**
     * @param int $fieldId
     * @param int $blockDefinitionId
     * @return string
     */
    public function disassociateBlockDefinitionWithField(int $fieldId, int $blockDefinitionId): string
    {
        $ee = $this->EE;

        $queryString1 = <<<EOF
DELETE a
FROM exp_blocks_atom a
LEFT JOIN exp_blocks_block b
ON a.block_id = b.id
WHERE field_id = :fieldId
  AND blockdefinition_id = :blockDefinitionId
EOF;

        $queryString2 = <<<EOF
DELETE FROM exp_blocks_block
WHERE field_id = :fieldId
  AND blockdefinition_id = :blockDefinitionId
EOF;

        $queryString3 = <<<EOF
DELETE FROM exp_blocks_blockfieldusage
WHERE field_id = :fieldId
  AND blockdefinition_id = :blockDefinitionId
EOF;

        $queryReplacements = [
            'fieldId' => $fieldId,
            'blockDefinitionId' => $blockDefinitionId
        ];

        // -------------------------------------------
        //  'blocks_disassociate' hook
        //
        if ($this->_hookExecutor->isActive(HookExecutor::DISASSOCIATE)) {
            $ee->db->trans_start();
            $this->_hookExecutor->disassociate([$queryString1, $queryString2, $queryString3], $queryReplacements);
            $ee->db->trans_complete();
        } else {
            $ee->db->trans_start();
            $this->query($queryString1, $queryReplacements);
            $this->query($queryString2, $queryReplacements);
            $this->query($queryString3, $queryReplacements);
            $ee->db->trans_complete();
        }
        //
        // -------------------------------------------

        return $ee->db->trans_status();
    }

    /**
     * @param $blockId
     * @param $atomDefinitionId
     * @param $data
     */
    public function setAtomData($blockId, $atomDefinitionId, $data)
    {
        $queryString = <<<EOF
INSERT INTO exp_blocks_atom
    (`block_id`, `atomdefinition_id`, `data`)
VALUES
    (:blockId, :atomDefinitionId, :data)
ON DUPLICATE KEY UPDATE
    data = :data
EOF;

        $queryReplacements = [
            'blockId' => $blockId,
            'atomDefinitionId' => $atomDefinitionId,
            // Don't allow null, column definition is NOT NULL
            'data' => $data === null ? '' : $data
        ];

        // -------------------------------------------
        //  'blocks_set_atom_data' hook
        //
        if ($this->_hookExecutor->isActive(HookExecutor::SET_ATOM_DATA)) {
            $this->_hookExecutor->setAtomData($queryString, $queryReplacements);
        } else {
            $this->query($queryString, $queryReplacements);
        }
        //
        // -------------------------------------------
    }

    /**
     * @param array $blockData
     * @param array $fieldData
     * @param array $treeData
     * @return int|null
     */
    public function createBlock(array $blockData = [], array $fieldData = [], array $treeData = [])
    {
        $queryString = <<<EOF
INSERT INTO exp_blocks_block
    (`blockdefinition_id`, `entry_id`, `field_id`, `order`, `parent_id`, `draft`, `depth`, `lft`, `rgt`, `componentdefinition_id`, `cloneable`)
VALUES
    (:blockDefinitionId, :entryId, :fieldId, :order, :parentId, :draft, :depth, :lft, :rgt, :componentDefinitionId, :cloneable)
EOF;

        $parentId = $treeData['parent_id'] ?? 0;
        $depth = $treeData['depth'] ?? 0;
        $lft = $treeData['lft'] ?? 0;
        $rgt = $treeData['rgt'] ?? 0;

        $queryReplacements = array_merge($blockData, [
            'parentId' => $parentId,
            'depth' => $depth,
            'lft' => $lft,
            'rgt' => $rgt,
        ]);

        // -------------------------------------------
        //  'blocks_create_block' hook
        //
        if ($this->_hookExecutor->isActive(HookExecutor::CREATE_BLOCK)) {
            $blockId = $this->_hookExecutor->createBlock(
                $queryString,
                $queryReplacements,
                $blockData['entryId'],
                $blockData['fieldId'],
                $fieldData
            );

            if ($blockId) {
                return $blockId;
            }
        } else {
            $this->query($queryString, $queryReplacements);
        }
        //
        // -------------------------------------------

        return $this->EE->db->insert_id();
    }

    /**
     * This should really be called updateBlock, but for legacy reasons (mostly b/c of the hook call) we'll keep the name.
     *
     * @param int   $blockId
     * @param int   $order
     * @param int   $entryId
     * @param int   $fieldId
     * @param array $fieldData
     * @param array $treeData
     * @param int   $draft
     * @return int|null
     */
    public function setBlockOrder(array $blockData = [], array $fieldData = [], array $treeData = [])
    {
        $queryString = "UPDATE exp_blocks_block SET `order` = :order, `parent_id` = :parentId, `depth` = :depth, `lft` = :lft, `rgt` = :rgt, `draft` = :draft WHERE id = :id";

        $parentId = $treeData['parent_id'] ?? 0;
        $depth = $treeData['depth'] ?? 0;
        $lft = $treeData['lft'] ?? 0;
        $rgt = $treeData['rgt'] ?? 0;

        $queryReplacements = array_merge($blockData, [
            'parentId' => $parentId,
            'depth' => $depth,
            'lft' => $lft,
            'rgt' => $rgt,
        ]);

        // -------------------------------------------
        //  'blocks_set_block_order' hook
        //
        if ($this->_hookExecutor->isActive(HookExecutor::SET_BLOCK_ORDER)) {
            return $this->_hookExecutor->setBlockOrder(
                $queryString,
                $queryReplacements,
                $blockData['entryId'],
                $blockData['fieldId'],
                $fieldData
            );
        }
        //
        // -------------------------------------------

        $this->query($queryString, $queryReplacements);

        return null;
    }

    /**
     * @param $blockId
     * @return mixed
     */
    public function deleteBlock($blockId)
    {
        $ee = $this->EE;

        $queryString1 = 'DELETE FROM exp_blocks_atom WHERE block_id = :blockId';
        $queryString2 = 'DELETE FROM exp_blocks_block WHERE id = :blockId';

        $queryReplacements = [
            'blockId' => $blockId
        ];

        // -------------------------------------------
        //  'blocks_delete_block' hook
        //
        if ($this->_hookExecutor->isActive(HookExecutor::DELETE_BLOCK)) {
            $ee->db->trans_start();
            $this->_hookExecutor->deleteBlock([$queryString1, $queryString2], $queryReplacements);
            $ee->db->trans_complete();
        } else {
            $ee->db->trans_start();
            $this->query($queryString1, $queryReplacements);
            $this->query($queryString2, $queryReplacements);
            $ee->db->trans_complete();
        }
        //
        // -------------------------------------------

        return $ee->db->trans_status();
    }

    /**
     * @param int $entryId
     */
    public function deleteBlocksByEntry($entryId)
    {
        $ee = $this->EE;

        /** @var CI_DB_result $query */
        $query = $ee->db->where('entry_id', $entryId)->get('blocks_block');
        $blocks = array_column($query->result_array(), 'id');

        // -------------------------------------------
        //  'blocks_delete_blocks_by_entry' hook
        //
        if ($this->_hookExecutor->isActive(HookExecutor::DELETE_BLOCKS_BY_ENTRY)) {
            $this->_hookExecutor->deleteBlocksByEntry($entryId);
        } else {
            if (!empty($blocks)) {
                $ee->db->where_in('id', $blocks)->delete('blocks_block');
                $ee->db->where_in('block_id', $blocks)->delete('blocks_atom');
            }
        }
        //
        // -------------------------------------------
    }

    /**
     * @param BlockGroup $blockGroup
     */
    public function createBlockGroup(BlockGroup $blockGroup)
    {
        $queryString = <<<EOF
INSERT INTO exp_blocks_blockgroup
    (`name`, `order`)
VALUES
    (:name, :order)
EOF;
        $ee = $this->EE;

        $this->query($queryString, [
            'name' =>$blockGroup->getName(),
            'order' => $blockGroup->getOrder(),
        ]);

        $blockGroup->setId($ee->db->insert_id());
    }

    /**
     * @param BlockGroup $blockGroup
     */
    public function updateBlockGroup(BlockGroup $blockGroup)
    {
        $queryString = <<<EOF
UPDATE exp_blocks_blockgroup
SET
    `name` = :name,
    `order` = :order
WHERE
    id = :id
EOF;

        $this->query($queryString, [
            'name' => $blockGroup->getName(),
            'order' => $blockGroup->getOrder(),
            'id' => $blockGroup->getId(),
        ]);
    }

    /**
     * @param $blockGroupId
     * @return bool
     */
    public function deleteBlockGroup($blockGroupId): bool
    {
        $ee = $this->EE;

        $queries = [
            <<<EOF
DELETE
FROM exp_blocks_blockgroup
WHERE id = :blockGroupId
EOF
            , <<<EOF
UPDATE exp_blocks_blockdefinition
SET
    group_id = 0
WHERE group_id = :blockGroupId
EOF
        ];

        $ee->db->trans_start();
        foreach ($queries as $queryString) {
            $this->query($queryString, [
                'blockGroupId' => $blockGroupId
            ]);
        }
        $ee->db->trans_complete();

        return $ee->db->trans_status();
    }

    /**
     * Create the core parts of a block definition. Note that this does not
     * create the atoms within a block definition.
     * @param BlockDefinition $blockDefinition
     */
    public function createBlockDefinition(BlockDefinition $blockDefinition)
    {
        $queryString = <<<EOF
INSERT INTO exp_blocks_blockdefinition
    (`group_id`, `name`, `shortname`, `instructions`, `deprecated`, `deprecated_note`, `preview_image`, `preview_icon`, `settings`, `is_component`, `is_editable`)
VALUES
    (:groupId, :name, :shortName, :instructions, :deprecated, :note, :previewImage, :previewIcon, :settings, :isComponent, :isEditable)
EOF;
        $ee = $this->EE;

        $this->query($queryString, [
            'groupId' => $blockDefinition->getGroupId(),
            'name' => $blockDefinition->getName(),
            'shortName' => $blockDefinition->getShortName(),
            'instructions' => $blockDefinition->getInstructions(),
            'deprecated' => $blockDefinition->isDeprecated(),
            'note' => $blockDefinition->getDeprecatedNote(),
            'previewImage' => $blockDefinition->getPreviewImage(),
            'previewIcon' => $blockDefinition->getPreviewIcon(),
            'settings' => json_encode($blockDefinition->getSettings()),
            'isComponent' => $blockDefinition->isComponent(),
            'isEditable' => $blockDefinition->isEditable(),
        ]);

        $blockDefinition->id = $ee->db->insert_id();
    }

    /**
     * Update the core parts of a block definition. Note that this does not
     * update the atoms within a block definition.
     * @param BlockDefinition $blockDefinition
     */
    public function updateBlockDefinition(BlockDefinition $blockDefinition)
    {
        $queryString = <<<EOF
UPDATE exp_blocks_blockdefinition
SET
    `group_id` = :groupId,
    `name` = :name,
    `shortname` = :shortName,
    `instructions` = :instructions,
    `deprecated` = :deprecated,
    `deprecated_note` = :note,
    `preview_image` = :previewImage,
    `preview_icon` = :previewIcon,
    `settings` = :settings,
    `is_component` = :isComponent,
    `is_editable` = :isEditable
WHERE
    id = :id
EOF;

        $this->query($queryString, [
            'groupId' => $blockDefinition->getGroupId(),
            'name' => $blockDefinition->getName(),
            'shortName' => $blockDefinition->getShortName(),
            'instructions' => $blockDefinition->getInstructions(),
            'deprecated' => $blockDefinition->isDeprecated(),
            'note' => $blockDefinition->getDeprecatedNote(),
            'previewImage' => $blockDefinition->getPreviewImage(),
            'previewIcon' => $blockDefinition->getPreviewIcon(),
            'settings' => json_encode($blockDefinition->getSettings()),
            'isComponent' => $blockDefinition->isComponent(),
            'isEditable' => $blockDefinition->isEditable(),
            'id' => $blockDefinition->getId(),
        ]);
    }

    /**
     * @param $blockDefinitionId
     * @return bool
     */
    public function deleteBlockDefinition($blockDefinitionId): bool
    {
        $ee = $this->EE;

        $queries = [
            <<<EOF
DELETE a
FROM exp_blocks_atom a
INNER JOIN exp_blocks_block b
  ON a.block_id = b.id
WHERE b.blockdefinition_id = :blockDefinitionId
EOF
            , <<<EOF
DELETE
FROM exp_blocks_atomdefinition
WHERE blockdefinition_id = :blockDefinitionId
EOF
            , <<<EOF
DELETE
FROM exp_blocks_block
WHERE blockdefinition_id = :blockDefinitionId
EOF
            , <<<EOF
DELETE
FROM exp_blocks_blockfieldusage
WHERE blockdefinition_id = :blockDefinitionId
EOF
            , <<<EOF
DELETE FROM exp_blocks_blockdefinition WHERE id = :blockDefinitionId
EOF
        ];

        $ee->db->trans_start();
        foreach ($queries as $queryString) {
            $this->query($queryString, [
                'blockDefinitionId' => $blockDefinitionId
            ]);
        }
        $ee->db->trans_complete();

        return $ee->db->trans_status();
    }

    /**
     * @param $blockDefinitionId
     * @param $atomDefinition
     */
    public function createAtomDefinition($blockDefinitionId, AtomDefinition $atomDefinition)
    {
        $queryString = <<<EOF
INSERT INTO exp_blocks_atomdefinition
  (`blockdefinition_id`, `shortname`, `name`, `instructions`, `order`, `type`, `settings`)
VALUES
  (:blockDefinitionId, :shortName, :name, :instructions, :order, :type, :settings)
EOF;
        $ee = $this->EE;

        $this->query($queryString, [
            'blockDefinitionId' => $blockDefinitionId,
            'name' => $atomDefinition->getName(),
            'shortName' => $atomDefinition->getShortName(),
            'instructions' => $atomDefinition->getInstructions(),
            'order' => $atomDefinition->getOrder(),
            'type' => $atomDefinition->getType(),
            'settings' => json_encode($atomDefinition->getSettings())
        ]);

        $atomDefinition->setId($ee->db->insert_id());
    }

    /**
     * @param $atomDefinition
     */
    public function updateAtomDefinition(AtomDefinition $atomDefinition)
    {
        $queryString = <<<EOF
UPDATE exp_blocks_atomdefinition
SET
    `name` = :name,
    `shortname` = :shortName,
    `instructions` = :instructions,
    `order` = :order,
    `type` = :type,
    `settings` = :settings
WHERE
    id = :id
EOF;

        $this->query($queryString, [
            'name' => $atomDefinition->getName(),
            'shortName' => $atomDefinition->getShortName(),
            'instructions' => $atomDefinition->getInstructions(),
            'order' => $atomDefinition->getOrder(),
            'type' => $atomDefinition->getType(),
            'settings' => json_encode($atomDefinition->getSettings()),
            'id' => $atomDefinition->getId(),
        ]);
    }

    /**
     * @param $atomDefinitionId
     * @return mixed
     */
    public function deleteAtomDefinition($atomDefinitionId)
    {
        $ee = $this->EE;

        $queryString1 = <<<EOF
DELETE FROM exp_blocks_atom WHERE atomdefinition_id = :atomDefinitionId
EOF;
        $queryString2 = <<<EOF
DELETE FROM exp_blocks_atomdefinition WHERE id = :atomDefinitionId
EOF;

        $ee->db->trans_start();
        $this->query($queryString1, ['atomDefinitionId' => $atomDefinitionId]);
        $this->query($queryString2, ['atomDefinitionId' => $atomDefinitionId]);
        $ee->db->trans_complete();

        return $ee->db->trans_status();
    }

    /**
     * @param $entryId
     * @param $fieldId
     * @param $data
     */
    public function updateFieldData($entryId, $fieldId, $data)
    {
        $ee = $this->EE;

        $updates = [
            'field_id_' . $fieldId => $data
        ];

        // -------------------------------------------
        //  'blocks_update_field_data' hook
        //
        if ($this->_hookExecutor->isActive(HookExecutor::UPDATE_FIELD_DATA)) {
            $this->_hookExecutor->updateFieldData($entryId, $fieldId, $data);
        } else {
            /** @var \ExpressionEngine\Service\Database\Query $db */
            $db = $ee->db;

            foreach ($updates as $fieldName => $updateData) {
                $tableName = App::getFieldTableName($fieldName);

                $db
                    ->where('entry_id', $entryId)
                    ->update($tableName, [
                        $fieldName => $updateData,
                    ]);
            }
        }
        //
        // -------------------------------------------
    }

    /**
     * @param $blockDefinitionId
     * @param $blockDefinitionName
     * @param $blockDefinitionShortName
     */
    public function copyBlockDefinition($blockDefinitionId, $blockDefinitionName, $blockDefinitionShortName)
    {
        $blockDefinition = $this->getBlockDefinitionById($blockDefinitionId);

        $blockDefinitionCopy = new BlockDefinition();
        $blockDefinitionCopy
            ->setId(null)
            ->setShortName($blockDefinitionShortName)
            ->setName($blockDefinitionName)
            ->setGroupId($blockDefinition->getGroupId())
            ->setInstructions($blockDefinition->getInstructions())
            ->setDeprecated($blockDefinition->isDeprecated())
            ->setDeprecatedNote($blockDefinition->getDeprecatedNote())
            ->setSettings($blockDefinition->getSettings())
            ->setIsComponent($blockDefinition->isComponent())
            ->setPreviewImage($blockDefinition->getPreviewImage())
            ->setPreviewIcon($blockDefinition->getPreviewIcon())
            ->setIsEditable($blockDefinition->isEditable())
        ;

        $this->createBlockDefinition($blockDefinitionCopy);

        foreach ($blockDefinition->getAtomDefinitions() as $atom) {
            $atom->id = null;
            $this->createAtomDefinition($blockDefinitionCopy->getId(), $atom);
        }

        if (!$blockDefinition->isComponent()) {
            return;
        }

        /** @var Block[] $componentBlocks */
        $componentBlocks = $this->getBlocksInComponent($blockDefinition->getId());
        $treeData = [];

        foreach ($componentBlocks as $block) {
            $treeData[$block->getId()] = $block->getTreeData();
        }

        $treeHelper = new TreeHelper();
        $treeHelper->setTreeData($treeData);

        $queryString = <<<EOF
INSERT INTO exp_blocks_components
    (`blockdefinition_id`, `componentdefinition_id`, `order`, `parent_id`, `depth`, `lft`, `rgt`)
VALUES
    (:blockDefinitionId, :componentDefinitionId, :order, :parentId, :depth, :lft, :rgt)
EOF;

        foreach ($componentBlocks as $index => $block) {
            $blockDefinitionId = $index === 0 ? $blockDefinitionCopy->getId() : $block->getDefinition()->getId();
            $treeData = $treeHelper->getTreeData();
            $blockTreeData = $treeData[$block->getId()];

            $queryReplacements = [
                'blockDefinitionId' => $blockDefinitionId,
                'componentDefinitionId' => $blockDefinitionCopy->getId(),
                'order' => $block->getOrder(),
                'parentId' => $blockTreeData['parent_id'],
                'depth' => $block->getDepth(),
                'lft' => $block->getLft(),
                'rgt' => $block->getRgt(),
            ];

            $this->query($queryString, $queryReplacements);
            $insertBlockId = $this->EE->db->insert_id();

            $treeData[$insertBlockId] = $treeData[$block->getId()];

            // If multiple new blocks are added, ensure that any child blocks have the correct parent_id
            $treeHelper->updateParentId($block->getId(), $insertBlockId);
        }
    }

    /**
     * @param BlockDefinition $blockDefinition
     * @param array $blocks
     */
    public function saveBlockComponent(BlockDefinition $blockDefinition, array $blocks = [])
    {
        $ee = $this->EE;

        // Delete old assignments first, easier this way.
        $ee->db
            ->where('componentdefinition_id', $blockDefinition->getId())
            ->delete('blocks_components');

        $treeOrder = $blocks['tree_order'] ?: '';

        unset($blocks['tree_order']);
        unset($blocks['blocks_new_block_0']);

        // If blocks were explicitly removed or it's no longer a component, or is allowed to have children there is
        // nothing else to save. The block is not considered a component.
        if (empty($blocks) ||
            !$blockDefinition->isComponent() ||
            $blockDefinition->getNestingRule('no_children') !== 'n'
        ) {
            return;
        }

        foreach ($blocks as $blockId => $block) {
            if (isset($block['deleted']) && $block['deleted'] === 'true') {
                unset($blocks[$blockId]);
                unset($_POST['field_id_0'][$blockId]);
            }
        }

        $treeHelper = new TreeHelper();
        $treeHelper->buildNestedSet(json_decode($treeOrder, true));

        // Add the definition that is the parent of the blocks as the root
        $rootBlock = [
            'blocks_block_new_root' => [
                'id' => 'blocks_block_new_root',
                'blockDefinitionId' => $blockDefinition->getId(),
            ]
        ];

        $blocks = $rootBlock + $blocks;
        $treeHelper->addRoot($rootBlock);

        $queryString = <<<EOF
INSERT INTO exp_blocks_components
    (`blockdefinition_id`, `componentdefinition_id`, `order`, `parent_id`, `depth`, `lft`, `rgt`, `cloneable`)
VALUES
    (:blockDefinitionId, :componentDefinitionId, :order, :parentId, :depth, :lft, :rgt, :cloneable)
EOF;

        foreach ($blocks as $blockId => $block) {
            if (Block::isExistingBlock($blockId)) {
                $blockId = intval(substr($blockId, 16));
            }

            $treeData = $treeHelper->getTreeData();
            $blockTreeData = $treeData[$blockId];

            $parentId = $blockTreeData['parent_id'] ?? 0;
            $depth = $blockTreeData['depth'] ?? 0;
            $lft = $blockTreeData['lft'] ?? 0;
            $rgt = $blockTreeData['rgt'] ?? 0;
            $order = isset($blockTreeData['order']) ? intval($blockTreeData['order']) : 0;

            $queryReplacements = [
                'blockDefinitionId' => $block['blockDefinitionId'],
                'componentDefinitionId' => $blockDefinition->getId(),
                'order' => $order,
                'parentId' => $parentId,
                'depth' => $depth,
                'lft' => $lft,
                'rgt' => $rgt,
                'cloneable' => $block['cloneable'] ?? 0,
            ];

            $this->query($queryString, $queryReplacements);
            $insertBlockId = $ee->db->insert_id();

            // If multiple new blocks are added, ensure that any child blocks have the correct parent_id
            $treeHelper->updateParentId($blockId, $insertBlockId);
        }
    }

    /**
     * @param int  $blockDefinitionId
     * @return array
     */
    public function getBlocksInComponent(int $blockDefinitionId)
    {
        $collection = [];
        $cacheKey = __FUNCTION__ . $blockDefinitionId;

        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $queryString = <<<EOF
SELECT
    bd.id as bd_id,
    bd.group_id as bd_group_id,
    bd.shortname as bd_shortname,
    bd.name as bd_name,
    bd.instructions as bd_instructions,
    bd.deprecated as bd_deprecated,
    bd.deprecated_note as bd_deprecated_note,
    bd.preview_image as bd_preview_image,
    bd.preview_icon as bd_preview_icon,
    bd.settings as bd_settings,
    bd.is_component as bd_is_component,
    bd.is_editable as bd_is_editable,
    b.id as b_id,
    b.order as b_order,
    b.parent_id as b_parent_id,
    b.depth as b_depth,
    b.lft as b_lft,
    b.rgt as b_rgt,
    b.cloneable as b_cloneable,
    b.componentdefinition_id as b_componentdefinition_id,
    ad.id as ad_id,
    ad.shortname as ad_shortname,
    ad.name as ad_name,
    ad.instructions as ad_instructions,
    ad.order as ad_order,
    ad.type as ad_type,
    ad.settings as ad_settings,
    a.id as a_id
FROM exp_blocks_components b
LEFT JOIN exp_blocks_blockdefinition bd
  ON b.blockdefinition_id = bd.id
LEFT JOIN exp_blocks_atomdefinition ad
    ON ad.blockdefinition_id = bd.id
LEFT JOIN exp_blocks_atom a
   ON a.block_id = b.id AND a.atomdefinition_id = ad.id
LEFT JOIN exp_blocks_blockgroup bg
    ON bd.group_id = bg.id
WHERE b.componentdefinition_id = :blockDefinitionId
ORDER BY b.order, ad.order
EOF;

        /** @var \CI_DB_result $query */
        $query = $this->query($queryString, [
            'blockDefinitionId' => $blockDefinitionId,
        ]);

        $currentBlock = NULL;
        $previousBlockId = NULL;

        /** @var \CI_DB_result $query */
        foreach ($query->result() as $row) {
            if ($previousBlockId !== intval($row->b_id)) {
                $previousBlockId = intval($row->b_id);
                if (!is_null($currentBlock)) {
                    $collection[] = $currentBlock;
                }

                $blockDefinition = new BlockDefinition();
                $blockDefinition
                    ->setId(intval($row->bd_id))
                    ->setGroupId(intval($row->bd_group_id))
                    ->setShortName($row->bd_shortname)
                    ->setName($row->bd_name)
                    ->setInstructions($row->bd_instructions ?: '')
                    ->setDeprecated($row->bd_deprecated ?: 0)
                    ->setDeprecatedNote($row->bd_deprecated_note ?: '')
                    ->setSettings(json_decode($row->bd_settings, true))
                    ->setIsComponent($row->bd_is_component ?: 0)
                    ->setIsEditable($row->bd_is_editable ?: 0)
                ;

                $currentBlock = new Block();
                $currentBlock
                    ->setId(intval($row->b_id))
                    ->setOrder(intval($row->b_order))
                    ->setDepth(intval($row->b_depth))
                    ->setParentId(intval($row->b_parent_id))
                    ->setLft(intval($row->b_lft))
                    ->setRgt(intval($row->b_rgt))
                    ->setCloneable(intval($row->b_cloneable))
                    ->setComponentDefinitionId(intval($row->b_componentdefinition_id))
                    ->setDefinition($blockDefinition)
                ;
            }

            $atomDefinition = new AtomDefinition();
            $atomDefinition
                ->setId(intval($row->ad_id))
                ->setShortName($row->ad_shortname)
                ->setName($row->ad_name)
                ->setInstructions($row->ad_instructions ?: '')
                ->setOrder(intval($row->ad_order))
                ->setType($row->ad_type)
                ->setSettings(json_decode($row->ad_settings, true))
            ;

            $currentBlock->getDefinition()->addAtomDefinition($row->ad_shortname, $atomDefinition);

            $atom = new Atom();
            $atom
                ->setId(intval($row->a_id))
                ->setDefinition($atomDefinition)
            ;

            $currentBlock->addAtom($atomDefinition->shortname, $atom);
        }

        if (!is_null($currentBlock)) {
            $collection[] = $currentBlock;
        }

        $this->cache[$cacheKey] = $collection;

        return $collection;
    }

    /**
     * Simple method to allow for named parameter binding.
     *
     * @param string $queryString
     * @param array $replacements
     * @return \CI_DB_result
     */
    public function query(string $queryString, array $replacements = [])
    {
        $ee = $this->EE;

        foreach ($replacements as $field => $value) {
            $value = $ee->db->escape($value);
            $queryString = str_replace(':'.$field, $value, $queryString);
        }

        return $ee->db->query($queryString);
    }
}
