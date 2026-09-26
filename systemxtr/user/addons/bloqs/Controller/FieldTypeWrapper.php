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

use ExpressionEngine\Legacy\Facade;
use \InvalidArgumentException;

class FieldTypeWrapper
{
    private $EE;
    private $_fieldtype;
    private $_contentType = null;
    private $_packageLoader;
    private $_shim;

    /**
     * @param \EE_Fieldtype $fieldtype
     * @param FieldTypePackageLoader $packageLoader
     * @param null $shim
     */
    function __construct(
        $ee,
        $fieldtype,
        $packageLoader,
        $shim = null
    ) {
        $this->EE = $ee;
        $this->_fieldtype = $fieldtype;
        $this->_contentType = $this->_getContentType();
        $this->_shim = $shim;

        if (is_null($packageLoader)) {
            throw new InvalidArgumentException('packageLoader should not be null');
        }

        $this->_packageLoader = $packageLoader;
    }

    /**
     * @return string
     */
    private function _getContentType(): string
    {
        if (!method_exists($this->_fieldtype, 'accepts_content_type')) {
            throw new InvalidArgumentException('Specified fieldtype does not have method accepts_content_type');
        }

        $supportsGrid = $this->_fieldtype->accepts_content_type('grid');
        $supportsBlocksLegacy = $this->_fieldtype->accepts_content_type('blocks/1');
        $supportsBloqs = $this->_fieldtype->accepts_content_type('bloqs/1');
        $random = 'blocks/' . rand(1000, 9999);
        $supportsRandom = $this->_fieldtype->accepts_content_type($random);

        // Legacy references
        if ($supportsBlocksLegacy && !$supportsRandom) {
            return 'blocks/1';
        }

        // 4.4.0 + references
        if ($supportsBloqs && !$supportsRandom) {
            return 'bloqs/1';
        }

        // Claims to support blocks but doesn't support Grid? Yeah, right.
        if (($supportsBlocksLegacy || $supportsBloqs) && !$supportsGrid) {
            return 'none';
        }

        if ($supportsGrid) {
            return 'grid';
        }

        return 'none';
    }

    /**
     * @return bool
     */
    function supportsGrid(): bool
    {
        // We would have thrown an error if it didn't, so it does.
        return $this->_contentType !== 'none';
    }

    /**
     * @return bool
     */
    function supportsBlocks(): bool
    {
        return $this->_contentType === 'blocks/1' || $this->_contentType === 'bloqs/1';
    }

    /**
     * @return string
     */
    function getContentType(): string
    {
        return $this->_contentType;
    }

    /**
     * @param $fieldtype
     * @param $atomDefinition
     * @param $rowName
     * @param $blockId
     * @param $fieldId
     * @param $entryId
     */
    public static function initializeFieldtype($fieldtype, $atomDefinition, $rowName, $blockId, $fieldId, $entryId)
    {
        $colId = $atomDefinition->id;

        // Assign settings to fieldtype manually so they're available like
        // normal field settings
        $fieldtype->_init(
            array(
                'field_id'      => $colId,
                'field_name'    => 'col_id_' . $colId,
                'content_id'    => $entryId,
                'content_type'  => 'blocks'
            )
        );

        $colRequired = $atomDefinition->settings['col_required'] ?? false;

        // Assign fieldtype column settings and any other information that
        // will be helpful to be accessible by fieldtypes
        $fieldtype->settings = array_merge(
            $atomDefinition->settings,
            array(
                'field_label'     => $atomDefinition->name,
                'field_required'  => $colRequired,
                'col_id'          => $colId,
                'col_name'        => $atomDefinition->shortname,
                'col_required'    => $colRequired,
                'entry_id'        => $entryId,
                'grid_field_id'   => $fieldId,
                'grid_row_id'     => $blockId,
                'grid_row_name'   => $rowName,
                'blocks_atom_id'  => $fieldId,
                'blocks_block_id' => $blockId,
                'blocks_block_name' => $rowName)
        );
    }

    /**
     * @param $atomDefinition
     * @param $rowName
     * @param $blockId
     * @param $fieldId
     * @param $entryId
     */
    public function reinitialize($atomDefinition, $rowName, $blockId, $fieldId, $entryId)
    {
        $this::initializeFieldtype(
            $this->_fieldtype,
            $atomDefinition,
            $rowName,
            $blockId,
            $fieldId,
            $entryId);
    }

    /**
     * @param $setting
     * @param $value
     */
    public function setSetting($setting, $value)
    {
        $this->_fieldtype->settings[$setting] = $value;
    }

    /**
     * @param $data
     */
    public function initialize($data)
    {
        $this->_fieldtype->_init($data);
    }

    /**
     * @param $modifier
     * @param $data
     * @param array $params
     * @param null $tagdata
     * @return mixed
     */
    public function replace($modifier, $data, $params = [], $tagdata = null)
    {
        /** @var \EE_Fieldtype $ft */
        $ft = $this->_fieldtype;
        $shim = $this->_shim;

        if (is_null($modifier)) {
            $modifier = 'tag';
        }

        if ($shim && method_exists($shim, 'grid_replace_' . $modifier)) {
            return call_user_func(array($shim, 'grid_replace_' . $modifier), $data, $params, $tagdata);
        }

        if ($shim && method_exists($shim, 'replace_' . $modifier)) {
            return call_user_func(array($shim, 'replace_' . $modifier), $data, $params, $tagdata);
        }

        if (method_exists($ft, 'grid_replace_' . $modifier)) {
            return call_user_func(array($ft, 'grid_replace_' . $modifier), $data, $params, $tagdata);
        }

        // Does grid_replace_tag_catchall supersede replace_modifier?

        if (method_exists($ft, 'replace_' . $modifier)) {
            return call_user_func(array($ft, 'replace_' . $modifier), $data, $params, $tagdata);
        }

        if ($modifier != 'tag' && method_exists($ft, 'replace_tag_catchall')) {
            return $ft->replace_tag_catchall($data, $params, $tagdata, $modifier);
        }

        // If there's a modifier that wasn't matched, do we fall back to replace_tag, throw an error, or return nothing?

        return $ft->replace_tag($data, $params, $tagdata);
    }

    /**
     * @param string $methodName
     * @param array $args
     * @param bool $passThrough
     * @return mixed|null
     */
    private function call($methodName, $args, $passThrough = false)
    {
        $this->_packageLoader->load();
        /** @var \EE_Fieldtype $ft */
        $ft = $this->_fieldtype;
        $shim = $this->_shim;

        if (
            count($args) &&
            $methodName === 'display_field' &&
            isset($this->EE->extensions) &&
            $this->EE->extensions->active_hook('custom_field_modify_data') === true
        ) {
            $args = $this->EE->extensions->call('custom_field_modify_data', $ft, $methodName, $args);
        }

        // Intercept the method on the fieldtype if another add-on is subscribing to the hook.
        // For example, Assets_ft->delete() is how Assets handles its own deletions, but the
        // hook blocks_intercept_assets_delete will intercept this call and immediately return.
        // Bloqs will not continue processing the request to the fieldtype.
        $ftName = strtolower(preg_replace('/(.*?)_ft$/', '$1', get_class($ft)));
        $interceptHookName = sprintf('blocks_intercept_%s_%s', $ftName, $methodName);

        if (isset($this->EE->extensions) && $this->EE->extensions->active_hook($interceptHookName) === true) {
            $result = $this->EE->extensions->call($interceptHookName, $ft, $args);
            $this->_packageLoader->unload();
            return $result;
        }

        if ($shim && method_exists($shim, 'grid_' . $methodName)) {
            $result = call_user_func_array(array($shim, 'grid_' . $methodName), $args);
        } elseif ($shim && method_exists($shim, $methodName)) {
            $result = call_user_func_array(array($shim, $methodName), $args);
        } elseif (method_exists($ft, 'block_' . $methodName)) {
            $result = call_user_func_array(array($ft, 'block_' . $methodName), $args);
        } elseif (method_exists($ft, 'grid_' . $methodName)) {
            $result = call_user_func_array(array($ft, 'grid_' . $methodName), $args);
        } elseif (method_exists($ft, $methodName)) {
            $result = call_user_func_array(array($ft, $methodName), $args);
        } elseif ($passThrough) { // Hrmm... this is suspect.
            $result = $args[0];
        } else {
            $result = null;
        }

        $this->_packageLoader->unload();

        return $result;
    }

    /**
     * @param $methodName
     * @param $args
     * @return mixed|null
     */
    private function callGridOnly($methodName, $args)
    {
        $this->_packageLoader->load();
        $ft = $this->_fieldtype;
        $shim = $this->_shim;

        if ($shim && method_exists($shim, $methodName)) {
            $result = call_user_func_array([$shim, $methodName], $args);
        } elseif (method_exists($ft, $methodName)) {
            $result = call_user_func_array([$ft, $methodName], $args);
        } else {
            $result = null;
        }

        $this->_packageLoader->unload();

        return $result;
    }

    /**
     * @param $data
     * @return mixed|null
     */
    public function preProcess($data)
    {
        return $this->call('pre_process', [$data], true);
    }

    /**
     * @param $data
     * @return mixed|null
     */
    public function displayField($data)
    {
        return $this->call('display_field', [$data], false);
    }

    /**
     * @param $data
     * @return mixed|null
     */
    public function displayPublishField($data)
    {
        return $this->call('display_field', [$data], false);
    }

    /**
     * @param $data
     * @return mixed|null
     */
    public function save($data)
    {
        return $this->call('save', [$data], true);
    }

    /**
     * @param $data
     * @return mixed|null
     */
    public function postSave($data)
    {
        return $this->call('post_save', [$data], true);
    }

    /**
     * @param $data
     * @return mixed|null
     */
    public function delete($data)
    {
        return $this->call('delete', [$data], true);
    }

    /**
     * @param $data
     * @return mixed|null
     */
    public function validate($data)
    {
        return $this->call('validate', [$data], true);
    }

    /**
     * @param $data
     * @return mixed|null
     */
    public function displaySettings($data)
    {
        $method_to_call = method_exists($this->_fieldtype, 'grid_display_settings')
            ? 'grid_display_settings'
            : 'display_settings';

        return $this->callGridOnly($method_to_call, [$data]);
    }

    /**
     * @param $data
     * @return mixed|null
     */
    public function validateSettings($data)
    {
        return $this->callGridOnly('grid_validate_settings', [$data]);
    }

    /**
     * @param $data
     * @return mixed|null
     */
    public function saveSettings($data)
    {
        return $this->call('save_settings', [$data], false);
    }
}
