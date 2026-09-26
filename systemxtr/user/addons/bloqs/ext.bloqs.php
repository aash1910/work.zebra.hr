<?php

use BoldMinded\Bloqs\Library\Basee\App;
use BoldMinded\Bloqs\Controller\HookExecutor;
use BoldMinded\Bloqs\Database\Adapter;
use BoldMinded\Bloqs\Library\Basee\License;
use BoldMinded\Bloqs\Library\Basee\Setting;
use BoldMinded\Bloqs\Library\Basee\Version;
use ExpressionEngine\Model\Channel\ChannelEntry;

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * ExpressionEngine Publisher Extension Class
 *
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

class Bloqs_ext
{
    /**
     * @var array
     */
    private $cache = [];

    /**
     * @var array
     */
    public $settings = [];

    /**
     * @var integer
     */
    public $version = BLOQS_VERSION;

    /**
     * @var string
     */
    public $settings_exist = 'n';

    public function __construct()
    {
        if (isset(ee()->session)) {
            if (!isset(ee()->session->cache['bloqs'])) {
                ee()->session->cache['bloqs'] = [];
            }
            $this->cache =& ee()->session->cache['bloqs'];
        }
    }

    public function core_boot()
    {
        // Do nothing...
    }

    /**
     * @return string
     */
    public function cp_js_end()
    {
        $scripts = [];

        // If another extension shares the same hook
        if (ee()->extensions->last_call !== false) {
            $scripts[] = ee()->extensions->last_call;
        }

        // Don't load unnecessary files when it's a frontedit modal.
        if (App::isFrontEditRequest()) {
            return implode('', $scripts);
        }

        $modules[] = $this->versionCheck();

        return implode('', $scripts) . implode('', $modules);
    }

    private function versionCheck(bool $checkForUpdates = true): string
    {
        if ($checkForUpdates) {
            $version = new Version();
            $latest = $version->setAddon('bloqs')->fetchLatest();

            if (isset($latest->version) && version_compare($latest->version, BLOQS_VERSION, '>')) {
                /** @var Setting $setting */
                $setting = ee('bloqs:Setting');

                $url = sprintf('https://boldminded.com/account/licenses?l=%s', $setting->get('license'));
                $script = License::getUpdateAvailableNotice('bloqs', $url);
                return preg_replace("/\s+/", " ", $script);
            }
        }

        return '';
    }

    /**
     * @param ChannelEntry $channelEntry
     * @throws Exception
     */
    public function after_channel_entry_save(ChannelEntry $channelEntry)
    {
        $searchValues = ee()->session->cache('bloqs', 'searchValues');

        if (!$searchValues) {
            return;
        }

        $hookExecutor = new HookExecutor(ee());
        $adapter = new Adapter(ee());

        foreach ($searchValues as $datum) {
            $hookExecutor->updateSearchValues($datum);
            $adapter->updateFieldData($datum['entryId'], $datum['fieldId'], $datum['fieldValue']);
        }
    }

    /**
     * @param ChannelEntry $channelEntry
     * @throws Exception
     */
    public function after_channel_entry_update(ChannelEntry $channelEntry)
    {
        $searchValues = ee()->session->cache('bloqs', 'searchValues');

        if (!$searchValues) {
            return;
        }

        // Yes this needs to be called again when an existing entry is updated, even though
        // after_channel_entry_save() also gets called prior to this getting called. A change was
        // made in EE's core in 10/2018 that started firing an update event that reset the native
        // field value to null, thus making it not searchable. https://boldminded.com/support/ticket/1852
        // So we call it again.
        $adapter = new Adapter(ee());

        foreach ($searchValues as $datum) {
            $adapter->updateFieldData($datum['entryId'], $datum['fieldId'], $datum['fieldValue']);
        }
    }

    /**
     * Since we don't have foreign key constraints and some installs may be using MyISAM, do things the hard way...
     *
     * @param ChannelEntry $channelEntry
     */
    public function after_channel_entry_delete(ChannelEntry $channelEntry)
    {
        $entryId = $channelEntry->getId();

        /** @var CI_DB_result $blocksQuery */
        $blocksQuery = ee()->db->where('entry_id', $entryId)->get('blocks_block');
        $blocks = [];

        foreach ($blocksQuery->result() as $row) {
            $blocks[] = $row->id;
        }

        if (!empty($blocks)) {
            ee()->db
                ->where_in('block_id', $blocks)
                ->delete('blocks_atom');

            ee()->db
                ->where_in('id', $blocks)
                ->delete('blocks_block');
        }
    }

    /**
     * @param $fieldName
     * @param $entry_ids
     * @param $depths
     * @param $sql
     * @return array
     * @throws \Exception
     */
    public function relationships_query($fieldName, $entry_ids, $depths, $sql)
    {
        // Before we attempt to call the ee:LivePreview service lets make sure its available.
        $isLivePreviewAvailable = App::isFeatureAvailable('livePreview');

        // We only want to use this hook when previewing...
        if (!$isLivePreviewAvailable || !ee('LivePreview')->hasEntryData()) {
            $cacheKey = md5($sql);

            if (isset($this->cache[$cacheKey])) {
                return $this->cache[$cacheKey];
            }

            $result = ee('db')->query($sql)->result_array();
            $this->cache[$cacheKey] = $result;

            return $result;
        }

        // Add a value that Publisher will reference in Publisher_relationship_hooks
        $this->cache['relationships_query_hook'] = true;

        $data = ee('LivePreview')->getEntryData();
        $result = [];

        /** @var \ExpressionEngine\Model\Channel\Channel $channel */
        $channel = ee('Model')->get('Channel', $data['channel_id'])->first();
        $allFields = $channel->getAllCustomFields();

        $bloqsFields = $allFields->filter(function($field) {
            return $field->field_type === 'bloqs';
        })->pluck('field_id');

        if (!$bloqsFields) {
            return $result;
        }

        $adapter = new Adapter(ee());

        foreach ($bloqsFields as $fieldId)
        {
            // Don't bother if we don't have the field, if it doesn't have the row data, or if it has no rows.
            if (!isset($data['field_id_' . $fieldId]) || empty($data['field_id_' . $fieldId])) {
                continue;
            }

            $columns = [];
            $blocks = $adapter->getBlockDefinitionsForField($fieldId);

            /** @var \BoldMinded\Bloqs\Entity\BlockDefinition $block */
            foreach ($blocks as $block) {
                /** @var \BoldMinded\Bloqs\Entity\AtomDefinition $atomDefinition */
                foreach ($block->getAtomDefinitions() as $atomDefinition) {
                    if ($atomDefinition->getType() === 'relationship') {
                        $columns[] = $atomDefinition->getId();
                    }
                }
            }

            $blockIdIterator = 1;

            // @todo this is empty even though I have a bloq with a rel field. Did something change I don't know about?
            if (is_array($data['field_id_' . $fieldId])) {
                foreach ($data['field_id_' . $fieldId] as $blockId => $block) {
                    if ($blockId === 'tree_order') {
                        continue;
                    }

                    foreach ($columns as $colId) {
                        if (isset($block['values']['col_id_' . $colId]['data'])) {
                            foreach ($block['values']['col_id_' . $colId]['data'] as $order => $id) {
                                if (!$id) {
                                    continue;
                                }
                                $result[] = [
                                    'L0_field' => $colId,
                                    'L0_grid_field_id' => $fieldId,
                                    'L0_grid_col_id' => $colId,
                                    'L0_grid_row_id' => $blockIdIterator,
                                    'L0_parent' => $blockIdIterator,
                                    'L0_id' => (int)$id,
                                    'order' => $order + 1,
                                ];
                            }
                        }
                    }

                    $blockIdIterator++;
                }
            }
        }

        return $result;
    }
}
