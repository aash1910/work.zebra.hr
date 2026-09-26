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

namespace BoldMinded\Bloqs\Entity;

class BlockDefinition
{
    // Public properties should be marked as private, but for backwards compatibility keeping as public

    /**
     * @var int
     */
    public $id;

    /**
     * @var string
     */
    public $shortname;

    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $instructions = '';

    /**
     * @var int
     */
    private $isComponent = 0;

    /**
     * @var int
     */
    private $isEditable = 0;

    /**
     * Is bloq programmatically hidden at all times, based on it's setting in the db?
     * Mostly reserved for the component wrapper definition.
     *
     * @var int
     */
    private $isHidden = 0;

    /**
     * Conditionally hide bloq in the menu at run time based on certain criteria
     *
     * @var int
     */
    private $isHiddenInMenu = 0;

    /**
     * Child blocks that make the component
     *
     * @var Block[]
     */
    public $blocks = [];

    /**
     * @var int
     */
    private $deprecated = 0;

    /**
     * @var string
     */
    private $deprecatedNote = '';

    /**
     * @var object
     */
    public $settings;

    /**
     * @var string
     */
    private $previewImage = '';

    /**
     * @var string
     */
    private $previewIcon = '';

    /**
     * @var int
     */
    public $groupId = 0;

    /**
     * @var array
     */
    public $atomDefinitions = [];

    public bool $selected;

    /**
     * These are not properties on BlockDefinition, or used by directly by Bloqs.
     * The Form_validation->setCallbackObject() method sets these properties on
     * the object used in the callback, but PHP 8.2+ does not allow setting dynamic
     * properties, so here we are.
     */
    public $lang;
    public $security;
    public $input;

    /**
     *
     * _has_unique_shortname
     *
     * @description: method to determine whether or not the value of a shortname is unique
     *
     *   This is used as a form_validation callback method
     *
     * @param value - string - input value
     * @param id - string - id of given BlockDefintion (null if no definition exists)
     *
     * return bool
     *
     * @todo Move this, it should have never been located on the model. It has multiple dependencies
     * and doesn't even use properties from the model.
     *
     **/
    public function hasUniqueShortname($value, $id = null)
    {
        ee()->db
            ->select('shortname')
            ->where('shortname', $value);

        if(!empty($id) ) {
            ee()->db->where('id !=', $id);
        }

        /** @var \CI_DB_result $block_def_results */
        $block_def_results = ee()->db->get('blocks_blockdefinition');

        /** @var \CI_DB_result $chan_fields_results */
        $chan_fields_results = ee()->db->select('field_name')
            ->where('field_name', $value)
            ->get('channel_fields');

        if( $block_def_results->num_rows() <= 0 && $chan_fields_results->num_rows <= 0) {
            return true;
        } else {
            ee()->form_validation->set_message(__FUNCTION__, lang('bloqs_blockdefinition_alert_unique'));
            return false;
        }
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return BlockDefinition
     */
    public function setId($id): BlockDefinition
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return int
     */
    public function getGroupId()
    {
        return $this->groupId;
    }

    /**
     * @param int $id
     * @return BlockDefinition
     */
    public function setGroupId($groupId): BlockDefinition
    {
        $this->groupId = $groupId;

        return $this;
    }

    /**
     * @return array
     */
    public function getAtomDefinitions()
    {
        return $this->atomDefinitions;
    }

    /**
     * @param $shortName
     * @return Atom|null
     */
    public function getAtomDefinition($shortName)
    {
        if (isset($this->atomDefinitions[$shortName])) {
            return $this->atomDefinitions[$shortName];
        }

        return null;
    }

    /**
     * @param array $atomDefinitions
     * @return BlockDefinition
     */
    public function setAtomDefinitions($atomDefinitions): BlockDefinition
    {
        $this->atomDefinitions = $atomDefinitions;

        return $this;
    }

    /**
     * @param $atom
     * @return BlockDefinition
     */
    public function addAtomDefinition($shortName, $definition): BlockDefinition
    {
        $this->atomDefinitions[$shortName] = $definition;

        return $this;
    }

    /**
     * @return bool
     */
    public function isHidden(): bool
    {
        return $this->isHidden === 1;
    }

    /**
     * @param int $isHidden
     * @return BlockDefinition
     */
    public function setIsHidden(int $isHidden = 0): BlockDefinition
    {
        $this->isHidden = $isHidden;

        return $this;
    }

    /**
     * @return bool
     */
    public function isHiddenInMenu(): bool
    {
        return $this->isHiddenInMenu === 1;
    }

    /**
     * @param int $isHiddenInMenu
     * @return $this
     */
    public function setIsHiddenInMenu(int $isHiddenInMenu = 0): BlockDefinition
    {
        $this->isHiddenInMenu = $isHiddenInMenu;

        return $this;
    }

    public function setIsComponent(int $isComponent = 0): BlockDefinition
    {
        $this->isComponent = $isComponent;

        return $this;
    }

    /**
     * @return bool
     */
    public function isComponent(): bool
    {
        return $this->isComponent === 1;
    }

    /**
     * @param int $isEditable
     * @return BlockDefinition
     */
    public function setIsEditable(int $isEditable = 0): BlockDefinition
    {
        $this->isEditable = $isEditable;

        return $this;
    }

    /**
     * @return bool
     */
    public function isEditable(): bool
    {
        return $this->isEditable === 1;
    }

    /**
     * @return string
     */
    public function getShortName(): string
    {
        return $this->shortname;
    }

    /**
     * @param string $shortname
     * @return BlockDefinition
     */
    public function setShortName($shortname): BlockDefinition
    {
        $this->shortname = $shortname;

        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return BlockDefinition
     */
    public function setName($name): BlockDefinition
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getInstructions(): string
    {
        return $this->instructions;
    }

    /**
     * @param string $instructions
     * @return BlockDefinition
     */
    public function setInstructions($instructions): BlockDefinition
    {
        $this->instructions = $instructions;

        return $this;
    }

    /**
     * @return bool
     */
    public function isDeprecated(): bool
    {
        return $this->deprecated;
    }

    /**
     * @param bool $deprecated
     * @return BlockDefinition
     */
    public function setDeprecated(bool $deprecated): BlockDefinition
    {
        $this->deprecated = $deprecated;

        return $this;
    }

    /**
     * @return string
     */
    public function getDeprecatedNote(): string
    {
        return $this->deprecatedNote;
    }

    /**
     * @param string $note
     * @return BlockDefinition
     */
    public function setDeprecatedNote(string $note = ''): BlockDefinition
    {
        $this->deprecatedNote = $note;

        return $this;
    }

    /**
     * @return object
     */
    public function getSettings()
    {
        return $this->settings;
    }

    /**
     * @param string $name
     * @return mixed|null
     */
    public function getSetting(string $name)
    {
        if (isset($this->settings[$name])) {
            return $this->settings[$name];
        }

        return null;
    }

    /**
     * @param object $settings
     * @return BlockDefinition
     */
    public function setSettings($settings): BlockDefinition
    {
        $this->settings = $settings;

        return $this;
    }

    /**
     * @return string
     */
    public function getPreviewImage(): string
    {
        return $this->previewImage;
    }

    /**
     * @param string $previewImage
     * @return BlockDefinition
     */
    public function setPreviewImage($previewImage): BlockDefinition
    {
        $this->previewImage = $previewImage;

        return $this;
    }

    /**
     * @return string
     */
    public function getPreviewIcon(): string
    {
        return $this->previewIcon;
    }

    /**
     * @param string $previewIcon
     * @return BlockDefinition
     */
    public function setPreviewIcon($previewIcon): BlockDefinition
    {
        $this->previewIcon = $previewIcon;

        return $this;
    }

    /**
     * @return array
     */
    public function getNestingRules()
    {
        return $this->settings['nesting'] ?? [];
    }

    /**
     * @param string $rule
     * @return null|mixed
     */
    public function getNestingRule(string $rule)
    {
        $nestingRules = $this->getNestingRules();
        if (isset($nestingRules[$rule])) {
            return $nestingRules[$rule];
        }

        return null;
    }

    public function canHaveChildren(): bool
    {
        return $this->getNestingRule('no_children') !== 'y';
    }

    /**
     * @return array|null
     */
    public function getBlocks(): array
    {
        return $this->blocks;
    }

    /**
     * @param array $blocks
     * @return BlockDefinition
     */
    public function setBlocks(array $blocks = []): BlockDefinition
    {
        $this->blocks = $blocks;

        return $this;
    }
}
