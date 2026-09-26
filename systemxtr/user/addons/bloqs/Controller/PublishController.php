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
use BoldMinded\Bloqs\Helper\StringHelper;
use BoldMinded\Bloqs\Helper\TreeHelper;
use BoldMinded\Bloqs\Entity\Atom;
use BoldMinded\Bloqs\Entity\AtomDefinition;
use BoldMinded\Bloqs\Entity\Block;
use BoldMinded\Bloqs\Entity\BlockDefinition;
use \stdClass as stdClass;

class PublishController {

    private $EE;
    private $_fieldId;
    private $_fieldName;
    private $_adapter;
    private $_ftManager;
    private $_hookExecutor;
    private $_logger;
    private $_prefix;

    /**
     * Collection of blockDefinitionVars indexed by BlockDefinition->id to be
     * used by the nesting/child block logic.
     *
     * @var array
     */
    private $blockDefinitionVarsCollection = [];

    /**
     * Create the controller
     *
     * @param object $ee The ExpressionEngine instance.
     * @param int $fieldId The database ID for the EE field itself.
     * @param string $fieldName The ExpressionEngine name for the field.
     * @param \BoldMinded\Bloqs\Database\Adapter $adapter The database adapter used for querying from and saving to the database.
     * @param \BoldMinded\Bloqs\Controller\FieldTypeManager $fieldTypeManager The object responsible for creating and loading field types.
     * @param HookExecutor $hookExecutor
     * @param \EE_Logger $logger
     */
    public function __construct($ee, $fieldId, $fieldName, $adapter, $fieldTypeManager, $hookExecutor, $logger)
    {
        $this->EE = $ee;
        $this->_fieldId = intval($fieldId);
        $this->_fieldName = $fieldName;
        $this->_adapter = $adapter;
        $this->_ftManager = $fieldTypeManager;
        $this->_hookExecutor = $hookExecutor;
        $this->_logger = $logger;
        $this->_prefix = 'blocks';
    }

    /**
     * Generate publish field HTML
     *
     * @param int   $entryId The Entry ID.
     * @param array $defaultBlockDefinitions
     * @param array $blocks  The blocks for this specific entry/field.
     * @return array
     */
    public function displayField(int $entryId, array $defaultBlockDefinitions = [], array $blocks = [])
    {
        $vars = [
            'blocks' => []
        ];

        $treeHelper = new TreeHelper();
        $tree = $treeHelper->buildBlockTree($blocks);

        /** @var Block $block */
        foreach ($tree as $block) {
            $vars['blocks'][] = $this->renderBlock($entryId, $block);
        }

        $blockDefinitions = $this->getBlockDefinitionsForDisplay($defaultBlockDefinitions);

        $vars['blockDefinitions'] = $this->createBlockDefinitionsVars($entryId, $blockDefinitions);
        $vars['blockDefinitionsVarsCollection'] = $this->blockDefinitionVarsCollection;
        $vars['jsonDefinitions'] = htmlspecialchars(json_encode($blockDefinitions), ENT_QUOTES, 'UTF-8');
        $vars['fieldId'] = $this->_fieldId;

        return $vars;
    }

    /**
     * @param array $defaultBlockDefinitions
     * @return array
     */
    private function getBlockDefinitionsForDisplay(array $defaultBlockDefinitions = []): array
    {
        $blockDefinitions = !empty($defaultBlockDefinitions) ?
            $defaultBlockDefinitions :
            $this->getAllowedBlockDefinitionsForField($this->_fieldId)
        ;

        /** @var BlockDefinition $blockDefinition */
        foreach ($blockDefinitions as $blockDefinition) {
            if ($blockDefinition->isComponent() && $blockDefinition->getBlocks()) {
                // Take a flat blocks array and create a nested tree so the view knows how to render them.
                $blockDefinition->setBlocks($this->buildComponentTree($blockDefinition));
            }
        }

        return $blockDefinitions;
    }

    /**
     * A bloq that is a component type needs to have it's children rendered as a nested tree
     *
     * @param BlockDefinition $blockDefinition
     * @return array
     */
    private function buildComponentTree(BlockDefinition $blockDefinition): array
    {
        return (new TreeHelper())->buildBlockTree($blockDefinition->getBlocks());
    }


    /**
     * @param $control
     * @param string $class BoldMinded\Bloqs\Entity\Atom|BoldMinded\Bloqs\Entity\AtomDefinition
     * @return bool
     */
    private function isValidAtomControl($control, $class = null)
    {
        if ($control !== false &&
            isset($control['html']) &&
            isset($control['atom']) &&
            ($control['atom'] instanceof $class)
        ) {
            return true;
        }

        return false;
    }

    /**
     * @param $entryId
     * @param Block $block
     * @return array
     */
    private function renderBlock($entryId, Block $block)
    {
        $prefix = $block->getPrefix();

        if (!$prefix) {
            $prefix = 'blocks_block_id_';
        }

        // Prevent duplicate prefixing
        if ($prefix && strpos($block->getId(), $prefix) !== false) {
            $prefix = '';
        }

        $names = $this->generateNames($prefix, $block->getId());

        if ($block->isNew()) {
            unset($names->deleted);
        }

        $block->deleted = "false";

        $blockVars = [
            'fieldNames' => $names,
            'visibility' => 'collapsed',
            'block' => $block,
            'controls' => []
        ];

        foreach ($block->getAtoms() as $atom) {
            $control = [
                'atom' => $atom
            ];

            if ($atom->getError()) {
                $blockVars['visibility'] = 'expanded';
            }

            $atomHtml = $this->publishAtom(
                $block->getId(),
                $atom->getDefinition(),
                $entryId,
                $prefix . $block->getId(),
                $block->atoms[$atom->definition->shortname]->value ?? ''
            );

            $control['html'] = $atomHtml;

            if ($this->_hookExecutor->isActive(HookExecutor::DISPLAY_ATOM)) {
                $hookResponse = $this->_hookExecutor->displayAtom($entryId, $block->getDefinition(), $atom->getDefinition(), $control);

                // Hook can return false, which means the atom will not be added to the publish page at all.
                // If it doesn't return false it needs to be valid.
                if ($this->isValidAtomControl($hookResponse, Atom::class)) {
                    $blockVars['controls'][] = $hookResponse;
                }
            } else {
                $blockVars['controls'][] = $control;
            }
        }

        if ($block->hasChildren()) {
            // Do not use getChildren() here...
            foreach ($block->children as &$child) {
                if ($child instanceof Block) {
                    $child = $this->renderBlock($entryId, $child);
                }
            }
        }

        return $blockVars;
    }

    /**
     * Displaying a field after a validation error is not as simple as when we're getting
     * a Blocks collection from adapter->getBlocks(), as in displayField(). So emulate a collection
     * of blocks that we get from a database response, then pass it off to renderField so it renders
     * nested data correctly, and also calls the hook.
     *
     * @param int   $entryId
     * @param array $defaultBlockDefinitions
     * @param       $data
     * @param bool  $isRevision
     * @return array
     *
     * @todo if a block has an invalid atom, then the user makes it valid, but also adds another
     *       block to the page while viewing the validated field, the newly added block isn't added
     *       to the database. In one case a parent/root block was not added, but its child was.
     *       Need to replicate and dig into this a bit more.
     */
    public function displayValidatedField(int $entryId, array $defaultBlockDefinitions = [], array $data = [], $isRevision = false): array
    {
        $vars = [];
        $vars['blocks'] = [];
        $blocks = [];

        $treeHelper = new TreeHelper();

        if (isset($data['tree_order']) && $data['tree_order']) {
            $treeHelper->buildNestedSet(json_decode($data['tree_order'], true));
        } elseif (isset($_POST['field_id_'. $this->_fieldId]['tree_order']) && $_POST['field_id_'. $this->_fieldId]['tree_order']) {
            $postedTreeData = json_decode($_POST['field_id_'. $this->_fieldId]['tree_order'], true);
            $treeHelper->buildNestedSet($postedTreeData);
        }

        $treeData = $treeHelper->getTreeData();

        // A couple of keys we want to remove because they are not valid blocks
        unset($data['blocks_new_block_0']);
        unset($data['tree_order']);

        $blockDefinitions = $this->getBlockDefinitionsForDisplay($defaultBlockDefinitions);

        foreach ($data as $id => $blockData) {
            $block = new Block();
            $block->setOrder($blockData['order'] ?? 0);
            $block->setComponentDefinitionId($blockData['componentDefinitionId'] ?? 0);
            $block->setCloneable($blockData['cloneable'] ?? 0);

            if ($isRevision || Block::isNewBlock($id)) {
                // Existing blocks from a revision need to have an integer based ID. When rendering the revision data
                // back to the page, it'll be prefixed with blocks_new_block_OLD_ID. Since it has "blocks_new" in it
                // when rendered to the DOM, if the entry is saved all the blocks will be treated as if they were new,
                // thus getting a new ID because the old blocks are deleted. The $data array received should have all
                // blocks prefixed with blocks_block_id_X or blocks_new_block_X, so we need to match those up with
                // what we have in the $treeData to keep the nesting structure intact.
                //
                // treeData may look similar to this:
                // [
                //     123 => ['order' => 1, 'parent_id' => 0, 'etc' => '...'],
                //     456 => ['order' => 2, 'parent_id' => 123, 'etc' => '...'],
                //     'blocks_new_block_1' => ['order' => 2, 'parent_id' => 456, 'etc' => '...'],
                //     789 => ['order' => 2, 'parent_id' => 456, 'etc' => '...'],
                //     'blocks_block_id_10 => [...]
                // ]
                $newBlockId = (int) str_replace(Block::PREFIX_NEW_BLOCK_ID, '', $id);
                $existingBlockId = (int) str_replace(Block::PREFIX_EXISTING_BLOCK_ID, '', $id);

                if (isset($treeData[$newBlockId])) {
                    $id = $newBlockId;
                    $block->setTreeData($treeData[$newBlockId]);
                } elseif (isset($treeData[$existingBlockId])) {
                    $id = $existingBlockId;
                    $block->setTreeData($treeData[$existingBlockId]);
                } elseif (isset($treeData[$id])) {
                    $block->setTreeData($treeData[$id]);
                }

                $block->setId($id);
                $block->setDeleted(false);
                $block->setDraft(intval($blockData['draft']));
                $block->setIsNew(true);
                $block->setPrefix(Block::PREFIX_NEW_BLOCK_ID);

                // If viewing a revision that was saved prior to camelCasing the variable name in v4.4.0
                // we'll get an error below when attempting to find the definition. Correct the revision data.
                if (isset($blockData['blockdefinitionid'])) {
                    $blockData['blockDefinitionId'] = $blockData['blockdefinitionid'];
                    unset($blockData['blockdefinitionid']);
                }

            } elseif (is_array($blockData) && !empty($blockData)) {

                if (isset($blockData['id'])) {
                    if (isset($treeData[$blockData['id']])) {
                        $block->setTreeData($treeData[$blockData['id']]);
                    }

                    $block->setId($blockData['id']);
                }
                if (isset($blockData['deleted'])) {
                    $block->setDeleted(filter_var($blockData['deleted'], FILTER_VALIDATE_BOOLEAN));
                }
                if (isset($blockData['draft'])) {
                    $block->setDraft(intval($blockData['draft']));
                }

                $block->setIsNew(false);
                $block->setPrefix(Block::PREFIX_EXISTING_BLOCK_ID);

            } else {
                // We don't have valid data to work with, so don't continue so we don't throw errors.
                continue;
            }

            $blockDefinition = $this->findBlockDefinition($blockDefinitions, intval($blockData['blockDefinitionId']));

            // If viewing an old revision that contains a block definition no longer available.
            if ($blockDefinition === null) {
                continue;
            }

            $block->setDefinition($blockDefinition);

            // If viewing an old revision where a block might not have any atom values, or if the block contains
            // single __hidden field b/c it is a component or a parent block where the children contain the actual
            // data and the parent is present just for hierarchy/nesting purposes.
            if ($isRevision && (!isset($blockData['values']))) {
                $atomDefinitions = $blockDefinition->getAtomDefinitions();

                // https://boldminded.com/support/ticket/3060
                // Fix scenario where Rel fields that are set to defer initialization saved
                // without any selected entries, then trying to view a revision.
                $relAtoms = array_filter($atomDefinitions, function ($atom) {
                    return $atom->type === 'relationship';
                });

                if (array_key_exists('__hidden', $atomDefinitions)) {
                    $blockData['values']['__hidden'] = '';
                } elseif (count($relAtoms) > 0) {
                    foreach ($relAtoms as $relAtom) {
                        $blockData['values'][$relAtom->getId()] = [];
                    }
                } else {
                    continue;
                }
            }

            foreach ($blockData['values'] as $valueId => $valueData) {
                if (substr($valueId, -6) === '_error') {
                    continue;
                }

                $atomDefinitionId = str_replace('col_id_', '', $valueId);

                // If the value ID is like col_id_blocks_10_something, we can ignore this.
                if (strpos($atomDefinitionId, '_')) {
                    continue;
                }

                $atomDefinition = $this->findAtomDefinition(
                    $block->getDefinition(),
                    intval($atomDefinitionId)
                );

                // Most likely to happen when viewing a revision. An old revision could contain old block data, which
                // in turn could contain old atom definitions that may no longer exist if the block was edited since
                // the revision was created.
                if (is_null($atomDefinition)) {
                    continue;
                }

                $atom = new Atom();
                $atom->setDefinition($atomDefinition);
                $atom->setValue($valueData ?? '');

                if (isset($blockData['values'][$valueId . '_error'])) {
                    $atom->setError($blockData['values'][$valueId . '_error']);
                }

                $block->addAtom($atomDefinition->getShortName(), $atom);
            }

            $blocks[] = $block;
        }

        $blockTree = $treeHelper->buildBlockTree($blocks);

        /** @var Block $block */
        foreach ($blockTree as $block) {
            $vars['blocks'][] = $this->renderBlock($entryId, $block);
        }

        $vars['blockDefinitions'] = $this->createBlockDefinitionsVars($entryId, $blockDefinitions);
        $vars['blockDefinitionsVarsCollection'] = $this->blockDefinitionVarsCollection;
        $vars['jsonDefinitions'] = htmlspecialchars(json_encode($blockDefinitions), ENT_QUOTES, 'UTF-8');
        $vars['fieldId'] = $this->_fieldId;

        return $vars;
    }

    /**
     * @param $prefix
     * @param $id
     * @return stdClass
     */
    protected function generateNames($prefix, $id)
    {
        $names = new stdClass();
        $names->baseName              = 'field_id_' . $this->_fieldId . '[' . $prefix . $id . ']';
        $names->id                    = 'field_id_' . $this->_fieldId . '[' . $prefix . $id . '][id]';
        $names->definitionId          = 'field_id_' . $this->_fieldId . '[' . $prefix . $id . '][blockDefinitionId]';
        $names->componentDefinitionId = 'field_id_' . $this->_fieldId . '[' . $prefix . $id . '][componentDefinitionId]';
        $names->order                 = 'field_id_' . $this->_fieldId . '[' . $prefix . $id . '][order]';
        $names->deleted               = 'field_id_' . $this->_fieldId . '[' . $prefix . $id . '][deleted]';
        $names->draft                 = 'field_id_' . $this->_fieldId . '[' . $prefix . $id . '][draft]';
        $names->cloneable            = 'field_id_' . $this->_fieldId . '[' . $prefix . $id . '][cloneable]';

        return $names;
    }

    /**
     * @param $entryId
     * @param array $blockDefinitions
     * @return array
     */
    protected function createBlockDefinitionsVars($entryId, $blockDefinitions)
    {
        $blockDefinitionVars = [];

        if (!isset(ee()->file_field)) {
            ee()->load->library('file_field');
        }

        /** @var BlockDefinition $blockDefinition */
        foreach ($blockDefinitions as $blockDefinition)
        {
            $componentId = 'template-block-' . $this->_fieldId . '-' . $blockDefinition->getShortName();

            $names = new stdClass();
            $names->baseName              = 'field_id_' . $this->_fieldId . '[blocks_new_block_0]';
            $names->order                 = 'field_id_' . $this->_fieldId . '[blocks_new_block_0][order]';
            $names->blockDefinitionId     = 'field_id_' . $this->_fieldId . '[blocks_new_block_0][blockDefinitionId]';
            $names->componentDefinitionId = 'field_id_' . $this->_fieldId . '[blocks_new_block_0][componentDefinitionId]';
            $names->draft                 = 'field_id_' . $this->_fieldId . '[blocks_new_block_0][draft]';
            $names->cloneable            = 'field_id_' . $this->_fieldId . '[blocks_new_block_0][cloneable]';

            $definitionVars = [
                'controls' => [],
                'definition' => $blockDefinition,
                'fieldNames' => $names,
                'previewImageParsed' => ee()->file_field->parse_string($blockDefinition->getPreviewImage()),
                'previewIconParsed' => ee()->file_field->parse_string($blockDefinition->getPreviewIcon()),
                'settings' => $blockDefinition->getSettings(),
                'componentId' => $componentId,
            ];

            foreach ($blockDefinition->getAtomDefinitions() as $atomDefinition)
            {
                $control = [
                    'atom' => $atomDefinition
                ];

                $atomHtml = $this->publishAtom(null, $atomDefinition, $entryId, null, null);

                $control['html'] = $atomHtml;

                if ($this->_hookExecutor->isActive(HookExecutor::DISPLAY_ATOM)) {
                    $hookResponse = $this->_hookExecutor->displayAtom($entryId, $blockDefinition, $atomDefinition, $control);

                    // Hook can return false, which means the atom will not be added to the publish page at all.
                    // If it doesn't return false it needs to be valid.
                    if ($this->isValidAtomControl($hookResponse, AtomDefinition::class)) {
                        $definitionVars['controls'][] = $hookResponse;
                    }
                } else {
                    $definitionVars['controls'][] = $control;
                }
            }

            $this->blockDefinitionVarsCollection[$blockDefinition->getId()] = $definitionVars;
            $blockDefinitionVars[] = $definitionVars;
        }

        return $blockDefinitionVars;
    }

    /**
     * Returns publish field HTML for a given atoms
     *
     * @param $blockId
     * @param AtomDefinition $atomDefinition Atom Definition.
     * @param int $entryId           Entry ID.
     * @param $rowId
     * @param $data
     * @return string
     */
    protected function publishAtom($blockId, AtomDefinition $atomDefinition, $entryId, $rowId, $data)
    {
        $fieldtype = $this->_ftManager->instantiateFieldtype(
            $atomDefinition,
            null,
            $blockId,
            $this->_fieldId,
            $entryId
        );

        if (is_null($fieldtype)) {
            if ($atomDefinition->getName()) {
                $message = sprintf(lang('bloqs_atomdefinition_ft_missing'), $atomDefinition->getName(), $atomDefinition->getType());
            } else {
                $message = lang('bloqs_atomdefinition_ft_missing_unknown');
            }

            return ee('CP/Alert')
                ->makeInline()
                ->cannotClose()
                ->asWarning()
                ->addToBody($message)
                ->render();
        }

        if (is_null($data)) {
            $data = '';
        }

        // Set up the block ID.
        $fieldtype->setSetting('grid_row_id', $blockId);
        $fieldtype->setSetting('blocks_block_id', $blockId);

        // Call the fieldtype's field display method and capture the output
        $display_field = $fieldtype->displayField($data);

        if (is_null($rowId)) {
            $rowId = 'blocks_new_block_0';
        }

        // Because several new fields are React fields with encoded settings which are used to render the
        // final html output instead of having access to the html here like all other basic fieldtypes.
        // If the HTML output is massive, e.g. displaying 6000 entries in a Relationship field, then
        // this match may fail and the field will not save correctly. https://boldminded.com/support/ticket/3021
        if (App::isGteEE4() && preg_match('/-react="(.*?)" data-input-value="(.*?)"/', $display_field, $matches)) {
            $settings = json_decode(base64_decode($matches[1]));

            if ($settings) {
                $fieldName = $matches[2];
                if (preg_match('/(.*?)\[(.*?)\](.*)/', $fieldName, $fieldNameMatches)) {
                    $newFieldName = $this->_fieldName . '[' . $rowId . '][values][' . $fieldNameMatches[1] . '][' . $fieldNameMatches[2] . ']' . $fieldNameMatches[3];
                } else {
                    $newFieldName = $this->_fieldName . '[' . $rowId . '][values][' . $fieldName . ']';
                }

                $settings->name = $newFieldName;
                $settings = base64_encode(json_encode($settings));

                $display_field = str_replace($matches[1], $settings, $display_field);
                // Add the disabled parameter to new fields so the hidden block components do not get rendered into a functional field.
                $disabled = !App::isRevisionViewRequest() && Block::isNewBlock($rowId) ? ' disabled="disabled"' : '';
                $display_field = preg_replace('/data-input-value="(.*?)"/', 'data-input-value="' . $newFieldName . '" ' . $disabled, $display_field);
            }
        }

        // Return the publish field HTML with namespaced form field names
        $display_field = StringHelper::namespaceInputs('data-input-value',
            $display_field,
            '$1data-input-value="'.$this->_fieldName.'['.$rowId.'][values][$3]$4"',
            ['a']
        );

        return StringHelper::namespaceInputs(
            'name',
            $display_field,
            '$1name="'.$this->_fieldName.'['.$rowId.'][values][$3]$4"'
        );
    }

    /**
     * This allows for bloqs to be assigned to a component and not the field itself as a standalone bloq.
     * Thus you can create a component that contains a bloq a user can't normally add to an entry.
     *
     * @param int $fieldId
     * @return array
     */
    private function getAllowedBlockDefinitionsForField(int $fieldId): array
    {
        // Get column data for the current field
        $blockDefinitionsForField = $this->_adapter->getBlockDefinitionsForField($fieldId);
        // Create a base collection. Could be empty if no individual fields are assigned, but components are
        $blockDefinitionsForFieldDictionary = array_column($blockDefinitionsForField, 'name', 'id');

        $allowedDefinitions = $blockDefinitionsForField;

        /** @var BlockDefinition $blockDefinition */
        foreach ($blockDefinitionsForField as $blockDefinition) {
            $blocksInComponent = $blockDefinition->getBlocks();
            /** @var Block $block */
            foreach ($blocksInComponent as $block) {
                // Create a reference dictionary to see what we already have
                $allowedDefinitionsDictionary = array_column($allowedDefinitions, 'name', 'id');

                if (!array_key_exists($block->getDefinition()->getId(), $allowedDefinitionsDictionary)) {
                    // If it appears in a component but is not directly assigned to the field as a standalone bloq,
                    // then hide it so it won't show up. We still need it's object in the page so it saves correctly,
                    // and so the HTML for the bloq can be inserted into the DOM.
                    if (!array_key_exists($block->getDefinition()->getId(), $blockDefinitionsForFieldDictionary)) {
                        $block->getDefinition()->setIsHiddenInMenu(1);
                    }

                    $allowedDefinitions[] = $block->getDefinition();
                }
            }
        }

        return $allowedDefinitions;
    }

    /**
     * @param $data
     * @param $entryId
     * @return array|string
     */
    public function validate($data, $entryId)
    {
        // Get column data for the current field
        $blockDefinitions = $this->getAllowedBlockDefinitionsForField($this->_fieldId);
        // Get the blocks currently assigned to the entry, if any
        $blocks = $this->_adapter->getBlocks($entryId, $this->_fieldId);

        return $this->processFieldData(
            $blocks,
            $blockDefinitions,
            'validate',
            $data,
            $entryId
        );
    }

    /**
     * @param array $data
     * @param int $entryId
     * @param bool $isRevision
     * @param bool $isClone
     */
    public function save($data, $entryId, bool $isRevision = false,  bool $isClone = false)
    {
        // If importing entries via a module action, such as Datagrab, don't try to re-save
        // blocks if there is no data. Otherwise it will delete existing blocks.
        if (defined('REQ') && REQ == 'ACTION' && (empty($data) || !is_array($data))) {
            return;
        }

        $treeHelper = new TreeHelper();

        // Visibly hidden fields will submit tree_order as a string, can't have that.
        if (isset($data['tree_order']) && is_array($data['tree_order'])) {
            $treeHelper->buildNestedSet($data['tree_order']);
        }

        // Get column data for the current field
        $blockDefinitions = $this->getAllowedBlockDefinitionsForField($this->_fieldId);
        // Get the blocks currently assigned to the entry, if any
        $blocks = $this->_adapter->getBlocks($entryId, $this->_fieldId);

        $data = $this->processFieldData(
            $blocks,
            $blockDefinitions,
            'save',
            $data,
            $entryId
        );

        $searchValues = array();

        if ($this->_hookExecutor->isActive(HookExecutor::PRE_SAVE_BLOCKS)) {
            $data = $this->_hookExecutor->preSaveBlocks($entryId, $this->_fieldId, $data);
        }

        foreach ($data['value'] as $colId => $blockData) {
            $blockDefinition = $this->findBlockDefinition(
                $blockDefinitions,
                intval($blockData['blockDefinitionId'])
            );

            // Yikes, what happened here?
            if ($blockDefinition === null) {
                continue;
            }

            $blockDefinitionId = intval($blockData['blockDefinitionId']);
            $componentDefinitionId = isset($blockData['componentDefinitionId']) ? intval($blockData['componentDefinitionId']) : 0;
            $cloneable = isset($blockData['cloneable']) ? intval($blockData['cloneable']) : 0;
            $order = isset($blockData['order']) ? intval($blockData['order']) : 0;
            $draft = isset($blockData['draft']) ? intval($blockData['draft']) : 0;

            // Always have the most up-to-date tree data
            $treeData = $treeHelper->getTreeData();
            $blockTreeData = [];

            if (Block::isExistingBlock($colId) && !$isClone) {
                $blockId = intval(substr($colId, 16));

                // Nestable?
                if (isset($treeData[$blockId])) {
                    $blockTreeData = $treeData[$blockId];
                    $order = $blockTreeData['order'];
                }

                if ($blockData['deleted'] == 'true') {
                    $this->_adapter->deleteBlock($blockId);

                    /* @var FieldTypeWrapper $fieldtype */
                    foreach ($blockData['fieldtypes'] as $atomDefinitionId => $fieldType) {
                        $atomDefinition = $this->findAtomDefinition($blockDefinition, (int) $atomDefinitionId);
                        $fieldType?->reinitialize($atomDefinition, $colId, $blockId, $this->_fieldId, $entryId);
                        $fieldType?->delete([$blockId]);
                    }

                    continue;
                }

                $result = $this->_adapter->setBlockOrder([
                    'id' => $blockId,
                    'blockDefinitionId' => $blockDefinitionId,
                    'componentDefinitionId' => $componentDefinitionId,
                    'cloneable' => $cloneable,
                    'entryId' => $entryId,
                    'fieldId' => $this->_fieldId,
                    'order' => $order,
                    'draft' => $draft,
                ], $data, $blockTreeData);

                if ($result) {
                    $blockId = $result;
                }
            } else {
                $revisionBlockId = null;

                // The next two conditionals handle nested data from a normal save, or when saving an entry revision.
                if ($isRevision || $isClone) {
                    // If it's an existing bloq then it is probably a clone action.
                    // Or it might be prefixed with 'new', but if its from a revision the ID won't match the int value
                    // in $treeData, so we have to see if the int values match up b/c in this case it already exists
                    // in the revision, but will get assigned a new block_id when the entry is saved again.
                    if (Block::isExistingBlock($colId)) {
                        $revisionBlockId = (int) str_replace(Block::PREFIX_EXISTING_BLOCK_ID, '', $colId);
                    } else {
                        $revisionBlockId = (int) str_replace(Block::PREFIX_NEW_BLOCK_ID, '', $colId);
                    }

                    if (isset($treeData[$revisionBlockId])) {
                        $blockTreeData = $treeData[$revisionBlockId];
                        $order = $blockTreeData['order'];
                    }
                }

                // New blocks added when editing a revision will also have the 'blocks_new_block_' prefix, but the
                // treeData will also have 'blocks_new_block_X', not the integer value from the previous save.
                if (isset($treeData[$colId])) {
                    $blockTreeData = $treeData[$colId];
                    $order = $blockTreeData['order'];
                }

                $blockId = $this->_adapter->createBlock([
                    'blockDefinitionId' => $blockDefinitionId,
                    'componentDefinitionId' => $componentDefinitionId,
                    'cloneable' => $cloneable,
                    'entryId' => $entryId,
                    'fieldId' => $this->_fieldId,
                    'order' => $order,
                    'draft' => $draft,
                ], $data, $blockTreeData);

                // If multiple new blocks are added, ensure that any child blocks have the correct parent_id
                if (Block::isNewBlock($colId) || $isClone) {
                    if ($revisionBlockId && isset($treeData[$revisionBlockId])) {
                        $treeHelper->updateParentId($revisionBlockId, $blockId);
                    } else {
                        $treeHelper->updateParentId($colId, $blockId);
                    }
                }
            }

            if ($this->_hookExecutor->isActive(HookExecutor::POST_SAVE_BLOCK)) {
                $blockData = $this->_hookExecutor->postSaveBlock($entryId, $this->_fieldId, $blockId, $blockData);
            }

            foreach ($blockData['values'] as $atomDefinitionId => $atomData) {
                $this->_adapter->setAtomData($blockId, $atomDefinitionId, $atomData);
            }

            // Run post_save on fieldtypes that need it.
            /* @var FieldTypeWrapper $fieldtype */
            foreach ($blockData['fieldtypes'] as $atomDefinitionId => $fieldtype) {
                /** @var AtomDefinition $atomDefinition */
                $atomDefinition = $this->findAtomDefinition($blockDefinition, (int) $atomDefinitionId);

                $value = $blockData['values'][$atomDefinitionId];

                $fieldtype->reinitialize($atomDefinition, $colId, $blockId, $this->_fieldId, $entryId);
                $fieldtype->postSave($value);

                if ($atomDefinition->isSearchable()) {
                    $searchValues[] = $value;
                }
            }
        }

        if (!empty($searchValues)) {
            // Grab existing search values if multiple Bloqs fields are being saved at the same time.
            $prevSearchValues = $this->EE->session->cache('bloqs', 'searchValues');

            $saveSearchValues = [
                [
                    'entryId' => $entryId,
                    'fieldId' => $this->_fieldId,
                    'fieldValue' => encode_multi_field($searchValues)
                ],
            ];

            if ($prevSearchValues) {
                $saveSearchValues = array_merge($prevSearchValues, $saveSearchValues);
            }

            // Handled in ext by after_channel_entry_update()
            $this->EE->session->set_cache('bloqs', 'searchValues', $saveSearchValues);
        }

        if ($this->_hookExecutor->isActive(HookExecutor::POST_SAVE)) {
            $blocks = $this->_adapter->getBlocks($entryId, $this->_fieldId);

            $context = [];
            $context['entry_id'] = $entryId;
            $context['field_id'] = $this->_fieldId;

            $this->_hookExecutor->postSave($blocks, $context);
        }
    }

    /**
     * @param Block[] $blocks
     * @param int $blockId
     * @return Block|null
     */
    protected function findBlock(array $blocks, int $blockId)
    {
        $indexed = array_column($blocks, null, 'id');

        if (array_key_exists($blockId, $indexed)) {
            return $indexed[$blockId];
        }

        return null;
    }

    /**
     * @param BlockDefinition[] $blockDefinitions
     * @param int $blockDefinitionId
     * @return BlockDefinition|null
     */
    protected function findBlockDefinition(array $blockDefinitions, int $blockDefinitionId)
    {
        $indexed = array_column($blockDefinitions, null, 'id');

        if (array_key_exists($blockDefinitionId, $indexed)) {
            return $indexed[$blockDefinitionId];
        }

        return null;
    }

    /**
     * @param BlockDefinition $blockDefinition
     * @param int $atomDefinitionId
     * @return AtomDefinition|null
     */
    protected function findAtomDefinition(BlockDefinition $blockDefinition, int $atomDefinitionId)
    {
        $indexed = array_column($blockDefinition->getAtomDefinitions(), null, 'id');

        if (array_key_exists($atomDefinitionId, $indexed)) {
            return $indexed[$atomDefinitionId];
        }

        return null;
    }

    /**
     * Processes a POSTed Grid field for validation for saving
     *
     * The main point of the validation method is, of course, to validate the
     * data in each cell and collect any errors. But it also reconstructs
     * the post data in a way that display_field can take it if there is a
     * validation error. The validation routine also keeps track of any other
     * input fields and carries them through to the save method so that those
     * values are available to fieldtypes while they run their save methods.
     *
     * The save method takes the validated data and gives it to the fieldtype's
     * save method for further processing, in which the fieldtype can specify
     * other columns that need to be filled.
     *
     * @param   array   The blocks
     * @param   array   The block definitions
     * @param   string  Method to process, 'save' or 'validate'
     * @param   array   Grid publish form data
     * @param   int     Entry ID of entry being saved
     * @return  string|array
     */
    protected function processFieldData($blocks, $blockDefinitions, $method, $data, $entryId)
    {
        $this->EE->load->helper('custom_field_helper');

        // We'll store our final values and errors here
        $finalValues = [];
        $errors = false;

        // $data = ' ' check is b/c of dumb things the Datagrab module does
        if (!$data || !is_array($data)) {
            return array('value' => $finalValues, 'error' => $errors);
        }

        // Make a copy of the files array so we can spoof it per field below
        $grid_field_name = $this->_fieldName;
        $files_backup = $_FILES;

        foreach ($data as $rowId => $blockData) {
            if ($rowId === 'blocks_new_block_0' || substr($rowId, 0, 7) !== 'blocks_') {
                // Don't save this. It's from the components.
                continue;
            }

            $blockId = str_replace('blocks_block_id_', '', $rowId);
            /** @var Block $block */
            $block = $this->findBlock($blocks, intval($blockId));

            /** @var BlockDefinition $blockDefinition */
            if (is_null($block)) {
                $blockDefinitionId = intval($blockData['blockDefinitionId']);
                $blockDefinition = $this->findBlockDefinition($blockDefinitions, $blockDefinitionId);
            } else {
                $blockDefinition = $this->findBlockDefinition($blockDefinitions, $block->definition->id);
            }

            if ($blockDefinition === null) {
                $this->_logger->developer(sprintf(lang('bloqs_blockdefinition_missing'), $blockData['blockDefinitionId']));

                continue;
            }

            if (isset($blockData['deleted']) && $blockData['deleted'] === 'true') {
                $finalValues[$rowId]['deleted'] = $blockData['deleted'];
                $finalValues[$rowId]['blockDefinitionId'] = $blockData['blockDefinitionId'];

                /** @var AtomDefinition $atomDefinition */
                foreach ($blockDefinition->getAtomDefinitions() as $atomDefinition) {
                    $fieldtype = $this->_ftManager->instantiateFieldtype(
                        $atomDefinition,
                        $rowId,
                        $blockId,
                        $this->_fieldId,
                        $entryId
                    );

                    $finalValues[$rowId]['fieldtypes'][$atomDefinition->getId()] = $fieldtype;
                }

                continue;
            } else {
                $finalValues[$rowId]['deleted'] = 'false';
            }

            if (isset($blockData['values'])) {
                $row = $blockData['values'];
            } else {
                $row = [];
            }

            $finalValues[$rowId]['id'] = $blockId;
            $finalValues[$rowId]['order'] = $blockData['order'];
            $finalValues[$rowId]['blockDefinitionId'] = $blockData['blockDefinitionId'];
            $finalValues[$rowId]['componentDefinitionId'] = $blockData['componentDefinitionId'] ?? 0;
            $finalValues[$rowId]['cloneable'] = $blockData['cloneable'] ?? 0;
            $finalValues[$rowId]['draft'] = $blockData['draft'];

            /** @var AtomDefinition $atomDefinition */
            foreach ($blockDefinition->getAtomDefinitions() as $atomDefinition) {
                $atom_id = 'col_id_'.$atomDefinition->getId();

                // Handle empty data for default input name
                if (!isset($row[$atom_id])) {
                    $row[$atom_id] = null;
                }

                // Assign any other input fields to POST data for normal access
                foreach ($row as $key => $value) {
                    $_POST[$key] = $value;

                    // If we're validating, keep these extra values around so
                    // fieldtypes can access them on save
                    if ($method === 'validate' && !isset($finalValues[$rowId]['values'][$key])) {
                        $finalValues[$rowId]['values'][$key] = $value;
                    }
                }

                $fieldtype = $this->_ftManager->instantiateFieldtype(
                    $atomDefinition,
                    $rowId,
                    $blockId,
                    $this->_fieldId,
                    $entryId
                );

                if ($fieldtype === null) {
                    $this->_logger->developer(sprintf(lang('bloqs_atomdefinition_missing'), $atomDefinition->getName(), $atomDefinition->getType()));

                    continue;
                }

                // Pass Grid row ID to fieldtype if it's an existing row
                if (strpos($rowId, 'blocks_block_id_') !== false) {
                    $fieldtype->setSetting('grid_row_id', $blockId);
                    $fieldtype->setSetting('blocks_block_id', $blockId);
                }

                // Inside Blocks our files arrays end up being deeply nested.
                // Since the fields access these arrays directly, we set the
                // FILES array to what is expected by the field for each
                // iteration.
                $_FILES = [];

                if (isset($files_backup[$grid_field_name])) {
                    $newFiles = [];

                    foreach ($files_backup[$grid_field_name] as $files_key => $value) {
                        if (isset($value[$rowId]['values'][$atom_id])) {
                            $newFiles[$files_key] = $value[$rowId]['values'][$atom_id];
                        }
                    }

                    $_FILES[$atom_id] = $newFiles;
                }

                // For validation, gather errors and validated data
                if ($method === 'validate') {
                    //run the fieldtypes validate method
                    $result = $fieldtype->validate($row[$atom_id]);

                    $error = $result;

                    // First, assign the row data as the final value
                    $value = $row[$atom_id];

                    // Here we extract possible $value and $error variables to
                    // overwrite the assumptions we've made, this is a chance for
                    // fieldtypes to correct input data or show an error message
                    if (is_array($result)) {
                        if (isset($result['value'])) {
                            $value = $result['value'];
                        }
                        if (isset($result['error'])) {
                            $error = $result['error'];
                        }
                    }

                    // Assign the final value to the array
                    $finalValues[$rowId]['values'][$atom_id] = $value;

                    // If column is required and the value from validation is empty,
                    // throw an error, except if the value is 0 because that can be
                    // a legitimate data entry
                    if (isset($atomDefinition->settings['col_required'])
                        && $atomDefinition->settings['col_required'] == 'y'
                        && empty($value)
                        && $value !== 0
                        && $value !== '0'
                    ) {
                        $error = lang('bloqs_field_required');
                    }

                    // Is this AJAX validation? If so, just return the result for the field we're validating
                    // Check for $this->EE global function b/c this is called in a PHPUnit test.
                    $fieldToValidate = $this->EE->input->post('ee_fv_field');
                    $atomBaseName = 'field_id_'.$this->_fieldId.'\['.$rowId.'\]\[values\]\['.$atom_id.'\]';
                    $nestedGridBaseName = $atomBaseName.'\[rows\]\[\S+\]\['.$atom_id.'\]\[\S+\]';

                    // If its a Relationship field...
                    if (substr($fieldToValidate, -8) === '[data][]') {
                        $fieldToValidate = substr($fieldToValidate, 0, -8);
                    }

                    // We're in a loop, so each field EE is attempting to validate, it's iterating over all the blocks
                    // and atoms, so it's attempting to validate fields other than the field EE is attempting to
                    // validate on mouse blur. Only validate the atom if the atom_id in the loop matches what is requested from ee_fv_field.
                    if (
                        function_exists('ee') && $this->EE->input->is_ajax_request()
                        &&
                        (
                            preg_match('/^' . $nestedGridBaseName . '$/', $fieldToValidate)
                            ||
                            preg_match('/^' . $atomBaseName . '$/', $fieldToValidate)
                        )
                    ) {
                        return $error;
                    }

                    // If there's an error, assign the old row data back so the
                    // user can see the error, and set the error message
                    if (is_string($error) && ! empty($error)) {
                        $finalValues[$rowId]['values'][$atom_id] = $row[$atom_id];
                        $finalValues[$rowId]['values'][$atom_id.'_error'] = $error;
                        $errors = lang('bloqs_validation_error');
                    }
                }
                // 'save' method
                elseif ($method === 'save')
                {
                    $result = $fieldtype->save($row[$atom_id]);

                    // Flatten array
                    if (is_array($result)) {
                        $result = encode_multi_field($result);
                    }

                    $finalValues[$rowId]['fieldtypes'][$atomDefinition->id] = $fieldtype;
                    $finalValues[$rowId]['values'][$atomDefinition->id] = $result;

                    if (is_null($block)) {
                        $finalValues[$rowId]['blockDefinitionId'] = $blockData['blockDefinitionId'];
                    }
                }

                # BB: WHAT?
                // Remove previous input fields from POST
                foreach ($row as $key => $value) {
                    unset($_POST[$key]);
                }
            }
        }

        // reset $_FILES in case it's used in other code
        $_FILES = $files_backup;

        return [
            'value' => $finalValues,
            'error' => $errors
        ];
    }
}
