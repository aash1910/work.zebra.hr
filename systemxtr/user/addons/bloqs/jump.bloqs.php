<?php

use BoldMinded\Bloqs\Database\Adapter;
use BoldMinded\Bloqs\Entity\BlockDefinition;
use ExpressionEngine\Service\JumpMenu\AbstractJumpMenu;


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

class Bloqs_jump extends AbstractJumpMenu
{
    protected static $items = [
        'bloqs' => [
            'icon' => 'fa-cog',
            'command' => 'block',
            'command_title' => 'Search for <b>blocks</b> titled <i>[block]</i>',
            'dynamic' => true,
            'requires_keyword' => false,
            'target' => 'searchBloqs',
        ],
    ];

    /**
     * @var Adapter
     */
    private $adapter;

    public function __construct()
    {
        $this->adapter = new Adapter(ee());
    }

    public function searchBloqs($searchKeywords = [])
    {
        $results = [];

        if (!$this->supportsJumpMenu()) {
            return $results;
        }

        $blockDefinitions = $this->adapter->getBlockDefinitions();

        $definitions = array_column($blockDefinitions, 'name', 'id');
        $searchResults = preg_grep('/.*' . implode(' ', $searchKeywords) . '.*/i', $definitions);

        if (empty($searchResults)) {
            return $results;
        }

        $filteredBlockDefinitions = array_filter($blockDefinitions, function ($definition) use ($searchResults) {
            return in_array($definition->name, $searchResults);
        });

        /** @var BlockDefinition $blockDefinition */
        foreach ($filteredBlockDefinitions as $blockDefinition) {
            // This should not be needed. Don't know why this is happening.
            // https://boldminded.com/support/ticket/2190
            if (is_null($blockDefinition)) {
                continue;
            }

            $results['bloq_' . $blockDefinition->getName()] = [
                'icon' => $blockDefinition->getPreviewIcon() ? 'fa-' . $blockDefinition->getPreviewIcon() : 'fa-pencil-alt',
                'command' => $blockDefinition->getName(),
                'command_title' => $blockDefinition->getName(),
                'command_context' => $blockDefinition->getInstructions(),
                'dynamic' => false,
                'requires_keyword' => false,
                'target' => 'block-definition?definitionId=' . $blockDefinition->id,
            ];
        }

        return $results;
    }

    /**
     * @return bool
     */
    private function supportsJumpMenu()
    {
        $addons = ee()->addons->get_installed();

        return (version_compare($addons['bloqs']['module_version'], BLOQS_VERSION, '>='));
    }
}
