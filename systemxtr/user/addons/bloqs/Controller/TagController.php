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
use BoldMinded\Bloqs\Database\Adapter;
use BoldMinded\Bloqs\Helper\TreeHelper;
use BoldMinded\Bloqs\Library\Basee\Parser;
use BoldMinded\Bloqs\Entity\Atom;
use BoldMinded\Bloqs\Entity\AtomDefinition;
use BoldMinded\Bloqs\Entity\Block;

/**
 * A parser and outputter for the root tag of the Blocks fieldtype.
 *
 * This class is primarily used from Blocks_ft::replace_tag
 */
class TagController
{
    private $EE;
    private FieldTypeManager $_ftManager;
    private int $_fieldId;
    private string $_prefix;
    private array $_fieldSettings;
    private HookExecutor $_hookExecutor;
    private array $blocks = [];
    private array $contexts = [];
    private string $initialTagData = '';
    private string $tagData = '';
    private array $entry = [];
    private array $hiddenBlocks = [];
    private array $blockVars = [];
    private array $blockVarsScoped = [];
    private array $blocksHistory = [];
    private array $fieldBlockVars = [];

    /**
     * Create the controller
     *
     * @param object $ee The ExpressionEngine instance.
     * @param int $fieldId The database ID for the EE field itself.
     * @param FieldTypeManager $fieldTypeManager The object responsible for creating and loading field types.
     * @param array $fieldSettings
     * @param HookExecutor $hookExecutor
     */
    public function __construct($ee, $fieldId, $fieldTypeManager, $fieldSettings, $hookExecutor)
    {
        $this->EE = $ee;
        $this->_prefix = 'blocks';
        $this->_fieldId = $fieldId;
        $this->_ftManager = $fieldTypeManager;
        $this->_fieldSettings = $fieldSettings;
        $this->_hookExecutor = $hookExecutor;
    }

    /**
     * @param array $blocks
     * @return array
     */
    public function buildContexts($blocks = []): array
    {
        $contexts = [];
        $index = 0;

        if ($this->isNestable()) {
            $treeHelper = new TreeHelper();
            $children = $treeHelper->findChildrenAndDescendants($blocks);

            $blocks = array_filter($blocks, function ($block) use ($children) {
                /** @var Block $block */
                return (in_array($block->getId(), $children));
            });
        }

        $total = count($blocks);

        // Create collection of block shortnames used with the total # of times each block occurs
        $totalsForBlockDefinitions = array_count_values(
            array_column(
                array_column($blocks, 'definition'),
                'shortname'
            )
        );

        // Create collection of shortnames with 0 as the starting value that is used as the index, then iterated
        // as it loops through and finds subsequent blocks of the same type and iterating the index each time.
        // Used in {blocks:count:of:type} variable.
        $indexForBlockDefinitions = array_map(
            function () {
                return 0;
            },
            $totalsForBlockDefinitions
        );

        /** @var Block $block */
        foreach ($blocks as $block) {
            if ($this->shouldHideBlock($block)) {
                $this->getHiddenChildBlocks($block);
                continue;
            }

            $shortName = $block->definition->shortname;
            $indexForBlockDefinition = $indexForBlockDefinitions[$shortName];

            $context = new TagOutputBlockContext(
                $block,
                $index,
                $total,
                $indexForBlockDefinition,
                $totalsForBlockDefinitions[$shortName]
            );

            $contexts[] = $context;

            $index++;
            $indexForBlockDefinitions[$shortName]++;
        }

        for ($i = 0; $i < count($contexts); $i++) {
            if (0 <= $i - 1) {
                $contexts[$i]->setPreviousContext($contexts[$i - 1]);
            }
            if ($i + 1 < count($contexts)) {
                $contexts[$i]->setNextContext($contexts[$i + 1]);
            }
        }

        return $contexts;
    }

    /**
     * @return bool
     */
    private function isNestable(): bool
    {
        return isset($this->_fieldSettings['nestable']) && $this->_fieldSettings['nestable'] === 'y';
    }

    /**
     * If a field is not set to nestable, then the logic in TreeHelper isn't applied.
     * Determine it's visibility as we're iterating the collection of blocks.
     *
     * @param Block $block
     * @return bool
     */
    private function shouldHideBlock(Block $block): bool
    {
        // If subscribed to the blocks_hide_block hook, and returns true,
        // or if the block is a child/descendant of an already hidden block.
        if (
            in_array($block->getId(), $this->hiddenBlocks) ||
            $this->_hookExecutor->hideBlock($block) === true
        ) {
            return true;
        }

        return $block->isDraft() && !$this->isNestable();
    }

    /**
     * Find all the children of a block that is marked as hidden.
     * shouldHideBlock() will also mark them as hidden, thus the contexts counts will be correct.
     * The descendants of a hidden block will not be added to the current $contexts array, thus
     * all the template variables will contain correct counts and the replace method will actually
     * end up performing less iterations.
     *
     * @param Block $block
     */
    private function getHiddenChildBlocks(Block $block)
    {
        /** @var Block $child */
        foreach ($block->getChildren() as $child) {
            $this->hiddenBlocks[] = $child->getId();

            if ($child->hasChildren()) {
                $this->getHiddenChildBlocks($child);
            }
        }
    }

    /**
     * @param array $params
     * @return array
     */
    private function filterFieldBlockVars($params = []): array
    {
        return array_filter($params, function ($key) {
            return $this->isBlockVar($key);
        }, ARRAY_FILTER_USE_KEY);
    }

    /**
     * The primary entry point for the Blocks parser
     *
     * @param string $tagdata The parsed template that EE gives.
     * @param \BoldMinded\Bloqs\Entity\Block[] $blocks The blocks that will be outputted.
     * @param array $channelRow Top-level row data that EE provides. Typically $this->row from the fieldtype.
     * @param array $params
     * @return string
     */
    public function replace($tagdata, $blocks, $channelRow, $params = []): string
    {
        $contexts = $this->buildContexts($blocks);
        $this->fieldBlockVars = $this->filterFieldBlockVars($params);
        $this->blocksHistory = [];

        $this->replaceNestedBlocks($contexts, $blocks, $tagdata, $channelRow);

        if (!empty($this->blockVars) || !empty($this->blockVarsScoped)) {
            //ee()->benchmark->mark('bloqs_update_vars_start');

            $markers = $this->matchBlockMarker($this->tagData);

            // Will update variables within a block or scoped by block ID
            $this->updateBlockVars($this->blockVars, $markers, true);

            // Will do a last pass on the final output and replace any outstanding unparsed variables
            $this->replaceBlockVarsGlobal();

            // Finally, cleanup unparsed vars, which at this point should be minimal to none b/c the user did not write the template code correctly.
            $this->tagData = preg_replace(['/{block_var_(.*?)}/', '/{bloq_var_(.*?)}/', '/{bloqs:get:(.*?)}/'],'', $this->tagData);

            //ee()->benchmark->mark('bloqs_update_vars_end');
        }

        return $this->tagData;
    }

    /**
     * @param array  $contexts
     * @param array  $blocks
     * @param string $tagData
     * @param array  $entry
     * @return string
     */
    private function replaceNestedBlocks(array $contexts = [], array $blocks = [], string $tagData = '', array $entry = []): string
    {
        $this->blocks = $blocks;
        $this->contexts = $contexts;
        $this->initialTagData = $tagData;
        $this->entry = $entry;

        $this->parseNestedBlocks();

        return $this->tagData;
    }

    /**
     * @param int $depth
     */
    private function parseNestedBlocks(int $depth = 0)
    {
        $parsedSections = [];
        $treeHelper = new TreeHelper();
        $depthCount = 1;

        /** @var TagOutputBlockContext $context */
        foreach ($this->contexts as $context) {
            $currentBlock = $context->getCurrentBlock();

            // Back at root left, so correct our depth
            if ($currentBlock->getDepth() === 0) {
                $depthCount = 1;
            }

            // Chart a new course if the depth has changed in either direction
            if ($currentBlock->getDepth() !== $depth) {
                $depthCount = 1;
                continue;
            }

            $sections = $this->EE->api_channel_fields->get_pair_field($this->initialTagData, $context->getShortname(), '');
            $sections = $this->handleEmptySections($context, $sections);
            $blockId = $currentBlock->getId();
            $blockName = $context->getShortname();
            $parentId = $currentBlock->getParentId();

            $parent = $treeHelper->findParent($this->blocks, $blockId);
            $parents = $treeHelper->findParents($this->blocks, $blockId);
            $children = $treeHelper->findChildren($this->blocks, $blockId);
            $siblings = $treeHelper->findSiblings($this->blocks, $blockId);
            $parentShortName = $parent ? $parent->getDefinition()->getShortName() : '';

            // Create some additional {blocks:X} variables
            $context->setChildren($children);
            $context->setSiblings($siblings);
            $context->setParentId($parentId);
            $context->setParentIds(array_column($parents,'id'));
            $context->setParentShortName($parentShortName);
            $context->setCountAtDepth($depthCount);
            $context->setDepth($depth + 1);
            $depthCount++;

            // There can be multiple sections.
            //
            // {block-field}
            //   {simple}
            //      <p>{content}</p>
            //   {/simple}
            //
            //   {simple}
            //      <div>Why would anybody do this?</div>
            //   {/simple}
            // {/block-field}
            //
            // Prefix the ID with the index so the blockId is always 0[some-int], 1[some-int] etc
            // then we'll sort the parsedSections to put them back into the same indexed order.
            foreach ($sections as $index => $section) {
                $sectionOutput = $this->_renderBlockSection(
                    $section[1],
                    $context,
                    $this->entry
                );

                // Start with any vars that were added as parameters on the {field}{/field} tag pair
                // and remove any block_var or bloq_var prefixes
                $this->blockVars[$blockId] = array_combine(
                    array_map([$this, 'removeBlockVarPrefixes'], array_keys($this->fieldBlockVars)),
                    $this->fieldBlockVars
                );

                /** @var Atom $atom */
                foreach ($currentBlock->getAtoms() as $atomName => $atom) {
                    if ($this->isBlockVar($atomName)) {
                        // Remove new and legacy prefixes so the array matches what is expected
                        $varName = $this->removeBlockVarPrefixes($atomName);
                        $this->blockVars[$blockId][$varName] = $atom->getValue();
                    }
                }

                $sectionOutput = $this->captureLocalVars($blockId, $sectionOutput);

                $parsedSections[$index][$blockId]['data'] = $this->_getMarkerStart($blockId, $index) . $sectionOutput . $this->_getMarkerEnd($blockId, $index);
                $parsedSections[$index][$blockId]['parentId'] = $parentId;
                $parsedSections[$index][$blockId]['blockId'] = $blockId;
                $parsedSections[$index][$blockId]['blockName'] = $blockName;
            }
        }

        // Remove unparsed variables
        if (empty($parsedSections)) {
            $this->tagData = preg_replace('/{bloqs:children:.*?}/', '', $this->tagData);
            $this->tagData = preg_replace('/{bloqs:child:.*?}/', '', $this->tagData);

            return;
        }

        if ($depth === 0) {
            foreach ($parsedSections as $sections) {
                foreach ($sections as $section) {
                    $this->tagData .= $section['data'];
                }
            }
        } else {
            foreach ($parsedSections as $sections) {
                foreach ($sections as $section) {
                    $this->tagData = str_replace(
                        sprintf('{bloqs:children:%d}', $section['parentId']),
                        $section['data'] . sprintf('{bloqs:children:%d}', $section['parentId']),
                        $this->tagData
                    );

                    $this->tagData = str_replace(
                        sprintf('{bloqs:child:%s:%d}', $section['blockName'], $section['parentId']),
                        $section['data'] . sprintf('{bloqs:child:%s:%d}', $section['blockName'], $section['parentId']),
                        $this->tagData
                    );
                }
            }
        }

        $this->parseNestedBlocks($depth + 1);
    }

    /**
     * @param string $variableName
     * @return string
     */
    private function removeBlockVarPrefixes(string $variableName): string
    {
        return str_replace(['bloq_var_', 'block_var_'], '', $variableName);
    }

    /**
     * @param int    $blockId
     * @param string $template
     * @return string
     */
    private function captureLocalVars(int $blockId, string $template = ''): string
    {
        //ee()->benchmark->mark(sprintf('bloqs_capture_vars_#%d_start', $blockId));

        // Create placeholder variables for the children and scoped variables based on the bloq in which they were found.
        // They'll be updated at a later point in the parsing process.
        $template = str_replace(
            '{bloqs:children}',
            sprintf('{bloqs:children:%d}', $blockId),
            $template
        );

        $template = preg_replace(
            '/{bloqs:child:(.*?)}/',
            sprintf('{bloqs:child:$1:%d}', $blockId),
            $template
        );

        $template = preg_replace(
            '/{bloqs:get:scoped:(.*?)}/',
            sprintf('{bloqs:get:scoped:%d:$1}', $blockId),
            $template
        );

        $parser = (new Parser($template))->findVariables('bloqs');

        $this->blockVars[$blockId] = array_replace($this->blockVars[$blockId], $parser->getVariables());
        $this->blockVarsScoped = array_replace_recursive($this->blockVarsScoped, $parser->getScopedVariables());

        //ee()->benchmark->mark(sprintf('bloqs_capture_vars_#%d_end', $blockId));

        return $parser->getTemplate();
    }

    /**
     * @param array $blockVars
     * @param array $markers
     * @param bool  $updateScopedVars
     */
    private function updateBlockVars(array $blockVars = [], array $markers = [], bool $updateScopedVars = false)
    {
        foreach ($markers as $marker) {
            $blockId = intval($marker['blockId']);
            $tagOpen = $marker['tagOpen'];
            $tagClose = $marker['tagClose'];

            // Pattern to find the content in our open/close tag pair
            $pattern = '/' . preg_quote($tagOpen) . '(.*?)' . preg_quote($tagClose) . '/us';

            // Always using the most up-to-date content, find the matched tag pair.
            preg_match($pattern, $this->tagData, $markerContentMatch);
            $markerContent = $markerContentMatch[1] ?? '';
            $originalMarkerContent = $markerContent;

            if ($updateScopedVars && array_key_exists($blockId, $this->blockVarsScoped)) {
                $markerContent = $this->replaceBlockVars($markerContent, $this->blockVarsScoped[$blockId], $blockId);
            }

            if (array_key_exists($blockId, $blockVars)) {
                $markerContent = $this->replaceBlockVars($markerContent, $blockVars[$blockId], $blockId);

                $this->tagData = str_replace($originalMarkerContent, $markerContent, $this->tagData);
            }

            // Now see if this bloq has any children and if so repeat the process
            $childMarkers = $this->matchBlockMarker($markerContent);

            if (!empty($childMarkers)) {
                $this->updateBlockVars($blockVars, $childMarkers, $updateScopedVars);
            }
        }
    }

    /**
     * @param int $blockId
     * @return Block|null
     */
    private function getBlock($blockId)
    {
        $key = array_search($blockId, array_column($this->blocks, 'id'));

        if ($key) {
            return $this->blocks[$key];
        }

        return null;
    }

    /**
     * @param string $template
     * @param array  $variables
     * @param int    $blockId
     * @return string
     */
    private function replaceBlockVars(string $template = '', array $variables = [], int $blockId = 0): string
    {
        foreach ($variables as $atomName => $atomValue) {
            // Seems to only be required in Live Preview mode since it loads data directly from the POST array
            if (is_array($atomValue)) {
                $atomValue = implode('|', array_filter($atomValue));
            }

            $vars = [
                // Legacy prefixed atom/field based variables
                sprintf('block_var_%s', $atomName) => $atomValue,
                // Newer prefixes as of v4.4.0
                sprintf('bloq_var_%s', $atomName) => $atomValue,
                // Template based Setter/Getter variables as of v4.9.0
                sprintf('bloqs:get:%s', $atomName) => $atomValue,
                sprintf('bloqs:get:scoped:%d:%s', $blockId, $atomName) => $atomValue,
            ];

            $template = $this->EE->functions->prep_conditionals($template, [$vars]);
            $template = $this->EE->TMPL->parse_variables($template, [$vars]);
        }

        return $template;
    }

    private function replaceBlockVarsGlobal()
    {
        foreach ($this->blockVars as $blockId => $blockVars) {
            $this->tagData = $this->replaceBlockVars($this->tagData, $blockVars, $blockId);
        }
    }

    /**
     * @param string $tagData
     * @return array
     */
    private function matchBlockMarker(string $tagData = ''): array
    {
        preg_match_all('/(?<tagOpen>{!-- bloqs:start:(?<blockId>\d+):(?<index>\d+) --})(?<content>.*)(?<tagClose>{!-- bloqs:end:\2:\3 --})/is', $tagData, $matches, PREG_SET_ORDER);

        return $matches;
    }

    /**
     * Display the total number of Blocks.
     *
     * @param $blocks
     * @param array $params Parameters given via the EE tag.
     * @return int
     */
    public function totalBlocks($blocks, $params): int
    {
        $contexts = $this->buildContexts($blocks);

        if (isset($params['type'])) {
            $type = $params['type'];
            $types = explode('|', $type);
            $count = 0;

            /** @var TagOutputBlockContext $context */
            foreach ($contexts as $context) {
                if (in_array($context->getShortname(), $types)) {
                    $count++;
                }
            }

            return $count;
        }

        return count($contexts);
    }

    /**
     * @param TagOutputBlockContext $context
     * @param array                 $sections
     * @return array
     */
    private function handleEmptySections(TagOutputBlockContext $context, array $sections = []): array
    {
        // Probably trying to render a block that does not have a tag pair in the template.
        if (empty($sections)) {
            $entryId = $this->entry['entry_id'] ?? '';
            $templateName = ($this->EE->TMPL->group_name ?? '') .'/'. ($this->EE->TMPL->template_name ?? '');

            $message = sprintf(
                lang('bloqs_missing_tag_pair'),
                $entryId,
                $context->getShortname(),
                $context->getShortname(),
                $templateName
            );

            if (App::userData('can_access_cp') && !bool_config_item('bloqs_disable_logging')) {
                $this->EE->load->library('logger');
                $this->EE->logger->developer($message, true);
            }

            $messageText = App::userData('member_id') ? '<!--' . $message . '-->' : '';

            // We have a missing block tag pair.
            // Create a blank tag pair so we can output a message in the proper location in the DOM.
            $sections[] = $this->_createSectionTagPair($context->getShortname(), $messageText);
        }

        return $sections;
    }

    /**
     * Outputting the Component Wrapper block is optional. Users' can either add a tag pair to their component, or negate
     * it entirely. If they negate it we need to prevent the parser from getting confused. After all a Component Wrapper
     * is a normal, but hidden, block definition. The following array is what get_pair_field() would return if the pair
     * was actually in the component. If a user wants to wrap their components they need to add the following to their
     * component:
     *
     * {__component_wrapper}
     * {/__component_wrapper}
     *
     * @param string $sectionName
     * @param string $content
     * @return array
     */
    private function _createSectionTagPair(string $sectionName, string $content = '')
    {
        // The \n are required here, otherwise some parsing doesn't work :/
        return [
            '',
            "\n" . $content . '{bloqs:children}' . "\n",
            [],
            "\n" .'{' . $sectionName . '}' . $content . '{bloqs:children}{/' . $sectionName . '}' . "\n",
        ];
    }

    /**
     * @param int $blockId
     * @param int $index
     * @return string
     */
    private function _getMarkerStart(int $blockId, int $index = 0)
    {
        return sprintf('{!-- bloqs:start:%d:%d --}', $blockId, $index);
    }

    /**
     * @param int $blockId
     * @param int $index
     * @return string
     */
    private function _getMarkerEnd(int $blockId, int $index = 0)
    {
        return sprintf('{!-- bloqs:end:%d:%d --}', $blockId, $index);
    }

    /**
     * Very similar to and inspired by Grid_parser->_parse_row()
     *
     * @param string $tagData
     * @param TagOutputBlockContext $context
     * @param array $channelRow
     * @return string
     * @throws \Exception
     */
    protected function _renderBlockSection(string $tagData, TagOutputBlockContext $context, array $channelRow = [])
    {
        $fieldName = ''; // It's just nothing. Period. This is actually the prefix, e.g. grid_field: but we don't use it.
        $entryId = $channelRow['entry_id'];
        $block = $context->getCurrentBlock();
        $blockContextVariables = $this->getContextVariables($context);

        $tagData = $this->parseRelationships($tagData, $block);
        $tagData = $this->prepareVariables($tagData, $block->getAtoms(), $blockContextVariables);

        // Get the special blocks variables and prepare to replace them.
        $switchVariables = $this->getSwitchVariables($tagData);
        $atomDefinitions = $block->getDefinition()->getAtomDefinitions();

        // Allow for all atoms to be prefixed with the block name to avoid scoping collisions
        // and parsing issues. For example: https://boldminded.com/support/ticket/2462
        // EE's Custom_field_pair.php->replace() will parse Bloqs, then go back and find something
        // like an Ansel field pair, try to parse it, and in doing so removes the pair from the template
        // chunk, so when it tries to replace the fully parsed Bloqs tag, which has already parsed the
        // child Ansel tag, it can't do the full replacement, thus the entire Bloqs tag prints out
        // as unparsed on the front-end. https://boldminded.com/support/ticket/3108
        // Remove the prefix and pretend it isn't there, we don't need it beyond this point.
        $prefix = $block->getDefinition()->getShortName() . ':';
        if (strpos($tagData, LD . $prefix) !== false) {
            $tagData = str_replace([
                LD . $prefix,
                LD . '/' . $prefix
            ], [
                LD,
                LD . '/'
            ], $tagData);
        }

        // Gather the variables to parse.
        $variables = ee('Variables/Parser')->extractVariables($tagData);

        // Work through field pairs first
        foreach ($variables['var_pair'] as $variable => $data) {
            $variable = $this->cleanVariableName($variable);

            if (!array_key_exists($variable, $atomDefinitions)) {
                continue;
            }

            $fieldChunks = $this->EE->api_channel_fields->get_pair_field($tagData, $variable, '');
            $fieldHasAtomData = isset($block->atoms[$variable]);

            foreach ($fieldChunks as $fieldChunkData) {
                list($modifier, $content, $params, $chunk) = $fieldChunkData;

                // Don't have data for the atom, but it does exist as part of the block definition
                if (!$fieldHasAtomData) {
                    // Give it a blank/default definition so it works in LivePreview
                    $atom = new Atom();
                    $atom
                        ->setId(0)
                        ->setValue('')
                        ->setDefinition($atomDefinitions[$variable]);

                    $block->atoms[$variable] = $atom;
                }

                $atom = $block->atoms[$variable];
                // Prepend the column ID with "blocks_" so it doesn't collide with any real grid columns.
                $columnId = 'col_id_' . $this->_prefix . '_' . $atom->definition->id;
                $channelRow[$columnId] = $atom->value;

                $replaceData = $this->replaceTag(
                    $atom->definition,
                    $this->_fieldId,
                    $entryId,
                    $block,
                    [
                        'modifier' => $modifier,
                        'params' => $params
                    ],
                    $channelRow,
                    $content
                );

                // Replace tag pair
                $tagData = str_replace($chunk, $replaceData ?? '', $tagData);
            }
        }

        foreach ($variables['var_single'] as $variable => $data) {
            $variableTag = LD . $variable . RD;

            // Get tag name, modifier and params for this variable. Use $field['field_name'] because that is the
            // actual atom name. $variable in this case could be an atom name with modifier, e.g. {image:url}
            $field = App::parseVariableProperties($variable, $fieldName . ':');
            $fieldHasAtomData = isset($block->atoms[$field['field_name']]);
            $variableIsAtom = $fieldHasAtomData && array_key_exists($field['field_name'], $atomDefinitions);

            // First handle any Blocks-specific variables, otherwise handle any single variables/atoms
            if (isset($blockContextVariables[$variable])) {
                $replaceData = $blockContextVariables[$variable];
            } elseif ($variableIsAtom && strpos($tagData, $variableTag) !== false) {
                // @todo Test this with File fields with a simple/single tag name. Something changed in EE 7.3,
                // probably with the chained modifiers, that caused this to stop working unless the File variable
                // is prefixed with the bloq name or in a bloq tag pair. Could I jam something in to restore functionality.
                $atom = $block->atoms[$field['field_name']];
                $columnId = 'col_id_' . $this->_prefix . '_' . $atom->definition->id;
                $channelRow[$columnId] = $atom->value;

                $replaceData = $this->replaceTag(
                    $atom->definition,
                    $this->_fieldId,
                    $entryId,
                    $block,
                    $field,
                    $channelRow
                );
            } else {
                $replaceData = $variableTag;

                if ($variable === '__hidden') {
                    $replaceData = '';
                }

                // Following block is taken directly from the Grid_parser
                if ($variableIsAtom && !empty($field['modifier'])) {
                    $parse_fnc = 'replace_' . $field['modifier'];

                    if (method_exists(ee('Variables/Parser'), $parse_fnc)) {
                        $replaceData = ee('Variables/Parser')->{$parse_fnc}($block->atoms[$field['field_name']]->value, $field['params']);
                    }
                }
            }

            // Handle {bloqs:switch="a|b|c"} variable values
            foreach ($switchVariables as $key => $val) {
                $switchVal = $switchVariables[$key][($context->getCountOfType() + count($val) - 1) % count($val)];
                $tagData = str_replace(LD . $key . RD, $switchVal, $tagData);
            }

            // When in Live Preview mode Relationship fields return an array of entry_ids, not a string.
            // Even when it is an array the tag appears to still render correctly.
            if (!is_array($replaceData)) {
                $tagData = $this->EE->functions->prep_conditionals($tagData, [
                    $variable => get_bool_from_string($replaceData)
                ]);

                // Finally, do the replacement
                if ($variableTag !== $replaceData) {
                    $tagData = str_replace($variableTag, $replaceData ?? '', $tagData);
                }
            }
        }

        return $tagData;
    }

    /**
     * Let EE's template parsing find field variables that might be prefixed,
     * or have parameters, e.g. {foo bar="bazz"}{/foo}
     *
     * @param string $variable
     * @return string
     */
    private function cleanVariableName(string $variable): string
    {
        $parsed = App::parseVariableProperties($variable);

        return $parsed['field_name'] ?? $variable;
    }

    /**
     * @param string $name
     * @return bool
     */
    private function isBlockVar($name)
    {
        return substr($name,0 , 10) === 'block_var_' || substr($name,0 , 9) === 'bloq_var_';
    }

    /**
     * @param string $tagData
     * @param Block  $block
     * @return null
     */
    protected function buildRelationshipParser(string $tagData, Block $block)
    {
        $this->EE->load->library('relationships_parser');
        $channel = $this->EE->session->cache('mod_channel', 'active');

        $relationships = array();

        foreach ($block->getAtoms() as $atom) {
            $atomDefinition = $atom->definition;
            if ($atomDefinition->type == 'relationship') {
                $relationships[$atomDefinition->shortname] = $atomDefinition->id;
            }
        }

        try {
            if (!empty($relationships)) {
                $relationshipParser = $this->EE->relationships_parser->create(
                    $channel->rfields,
                    array($block->id), // Um, only gonna parse this one?
                    $tagData,
                    $relationships, // field_name => field_id
                    $this->_fieldId
                );
            } else {
                $relationshipParser = null;
            }
        } catch (\EE_Relationship_exception $e) {
            $relationshipParser = null;
        }

        return $relationshipParser;
    }

    /**
     * @param string $tagData
     * @param Block  $block
     * @return string
     */
    protected function parseRelationships(string $tagData, Block $block)
    {
        $relationshipParser = $this->buildRelationshipParser($tagData, $block);

        $channel = $this->EE->session->cache('mod_channel', 'active');

        $rowId = $block->id;

        if ($relationshipParser)
        {
            try
            {
                $tagData = $relationshipParser->parse($rowId, $tagData, $channel);
            }
            catch (\EE_Relationship_exception $e)
            {
                $this->EE->TMPL->log_item($e->getMessage());
            }
        }

        return $tagData;
    }

    /**
     * @param string $tagData
     * @param array  $atoms
     * @param array  $blockContextVariables
     * @return string
     */
    protected function prepareVariables(string $tagData = '', array $atoms = [], array $blockContextVariables = []): string
    {
        // Map column names to their values in the DB
        foreach ($atoms as $atom) {
            $blockContextVariables[$atom->getDefinition()->getShortName()] = $atom->getValue();
        }

        return $this->EE->functions->prep_conditionals($tagData, $blockContextVariables);
    }

    /**
     * @param TagOutputBlockContext $context
     * @return array
     */
    public function getContextVariables(TagOutputBlockContext $context)
    {
        $vars = [];

        // Should all of this bloqs:code go into the context?
        $vars['bloqs:id'] = $context->getBlockId();
        $vars['bloqs:shortname'] = $context->getShortname();
        $vars['bloqs:index'] = $context->getIndex();
        $vars['bloqs:count'] = $context->getCount();
        $vars['bloqs:depth'] = $context->getDepth();
        $vars['bloqs:total_bloqs'] = $context->getTotal();
        $vars['bloqs:total_rows'] = $context->getTotal();
        $vars['bloqs:index:of:type'] = $context->getIndexOfType();
        $vars['bloqs:count:of:type'] = $context->getCountOfType();
        $vars['bloqs:count:at:depth'] = $context->getCountAtDepth();
        $vars['bloqs:total_bloqs:of:type'] = $context->getTotalOfType();
        $vars['bloqs:total_rows:of:type'] = $context->getTotalOfType();
        $vars['bloqs:previous:id'] = $context->getPreviousBlockId();
        $vars['bloqs:previous:shortname'] = '';
        $vars['bloqs:next:id'] = $context->getNextBlockId();
        $vars['bloqs:next:shortname'] = '';
        $vars['bloqs:is:component'] = $context->getCurrentBlock()->getDefinition()->isComponent();

        // Nesting specific variables
        $vars['bloqs:is:root'] = $context->getParentId() === 0;
        $vars['bloqs:root:id'] = $context->getRootBlockId();
        $vars['bloqs:root:shortname'] = $context->getRootShortName();
        $vars['bloqs:is:first_child'] = $context->isFirstChild();
        $vars['bloqs:is:last_child'] = $context->isLastChild();
        $vars['bloqs:parent:id'] = $context->getParentId();
        $vars['bloqs:parent:ids'] = implode('|', $context->getParentIds());
        $vars['bloqs:parent:shortname'] = $context->getParentShortName();
        $vars['bloqs:children:total_bloqs'] = $context->getTotalChildren();
        $vars['bloqs:children:total_rows'] = $context->getTotalChildren();
        $vars['bloqs:siblings:total_bloqs'] = $context->getTotalSiblings();
        $vars['bloqs:siblings:total_rows'] = $context->getTotalSiblings();

        /** @var TagOutputBlockContext $previousContext */
        $previousContext = $context->getPreviousContext();
        if (!is_null($previousContext)) {
            $vars['bloqs:previous:shortname'] = $previousContext->getShortname();
        }

        /** @var TagOutputBlockContext $nextContext */
        $nextContext = $context->getNextContext();
        if (!is_null($nextContext)) {
            $vars['bloqs:next:shortname'] = $nextContext->getShortname();
        }

        $allVars = '';
        foreach ($vars as $key => $value) {
            $allVars .= LD.$key.RD .' = '. $value ."\n";
        }

        // Create a variable to use for debugging
        $vars['bloqs:all_vars'] = $allVars;

        // Create aliases for backwards compatibility (pre 4.4.0)
        foreach ($vars as $name => $value) {
            $vars[str_replace('bloqs', 'blocks', $name)] = $value;
        }

        return $vars;
    }

    /**
     * @param string                $tagdata
     * @return array
     */
    private function getSwitchVariables(string $tagdata = '')
    {
        $switch = [];

        if (preg_match_all("/" . LD . "(bloqs:switch\s*=.+?)" . RD . "/i", $tagdata, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $sparam = ee('Variables/Parser')->parseTagParameters($match[1]);

                if (isset($sparam['bloqs:switch'])) {
                    $sopt = explode("|", $sparam['bloqs:switch']);

                    $switch[$match[1]] = $sopt;
                }
            }
        }

        return $switch;
    }

    /**
     * @param AtomDefinition $atomDefinition
     * @param int $fieldId
     * @param int $entryId
     * @param Block $block
     * @param array $field
     * @param array $data
     * @param string $content
     * @return string
     * @throws \Exception
     */
    protected function replaceTag(
        AtomDefinition $atomDefinition,
        int $fieldId,
        int $entryId,
        Block $block,
        array $field,
        array $data,
        string $content = ''
    ) {
        $colId = $this->_prefix . '_' . $atomDefinition->getId();
        $blockId = $block->getId();

        ee()->benchmark->mark(sprintf('bloqs_render_bloq_#%s/%s_start', $blockId, $atomDefinition->getName()));

        $fieldtype = $this->_ftManager->instantiateFieldtype(
            $atomDefinition,
            $block->getOriginalBlockName(),
            $blockId,
            $fieldId,
            $entryId
        );

        // Return the raw data if no fieldtype found
        if (!$fieldtype) {
            $response = $this->EE->typography->parse_type($this->EE->functions->encode_ee_tags($data['col_id_' . $colId]));

            ee()->benchmark->mark(sprintf('bloqs_render_bloq_#%s/%s_end', $blockId, $atomDefinition->getName()));

            return $response;
        }

        // Determine the replace function to call based on presence of modifier
        $modifier = $field['modifier'];

        // Sent to catchall if modifier function doesn't exist (added for better EE 7.3 support)
        if (array_key_exists('full_modifier', $field)) {
            $modifier = $field['full_modifier'];
        }

        // A conditional was changed in 7.3 in the ft.file.php file that does a strict false type check.
        // https://github.com/ExpressionEngine/ExpressionEngine/issues/3507
        if ($atomDefinition->getType() === 'file' && !$content) {
            $content = false;
        }

        $fieldtype->initialize(array(
            'row' => $data,
            'content_id' => $entryId
        ));

        // Add row ID to settings array
        $fieldtype->setSetting('grid_row_id', $blockId);
        $fieldtype->setSetting('blocks_block_id', $blockId);

        $response = $fieldtype->replace(
            $modifier ?: null,
            $fieldtype->preProcess($data['col_id_' . $colId]),
            $field['params'],
            $content
        );

        ee()->benchmark->mark(sprintf('bloqs_render_bloq_#%s/%s_end', $blockId, $atomDefinition->getName()));

        return $response;
    }
}
