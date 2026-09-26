<?php

use BoldMinded\Bloqs\Controller\ModalController;
use BoldMinded\Bloqs\Library\Basee\App;
use BoldMinded\Bloqs\Library\Basee\Cache;
use BoldMinded\Bloqs\Library\Basee\Trial;
use BoldMinded\Bloqs\Entity\BlockDefinition;
use BoldMinded\Bloqs\Service\FormSecret;
use BoldMinded\Bloqs\Controller\FieldTypeFilter;
use BoldMinded\Bloqs\Controller\FieldTypeManager;
use BoldMinded\Bloqs\Controller\HookExecutor;
use BoldMinded\Bloqs\Controller\PublishController;
use BoldMinded\Bloqs\Controller\TagController;
use BoldMinded\Bloqs\Database\Adapter;
use BoldMinded\Bloqs\Helper\UrlHelper;
use BoldMinded\Bloqs\Service\MaxInputVars;

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

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

class Bloqs_ft extends EE_Fieldtype
{
    /**
     * @var string
     */
    public $pkg = 'bloqs';

    /**
     * @var array
     */
    public $info = [
        'name' => 'Bloqs',
        'version' => BLOQS_VERSION
    ];

    /**
     * @var bool
     */
    public $has_array_data = true;

    /**
     * @var bool
     */
    public $entry_manager_compatible = true;

    /**
     * @var []|null
     */
    public $cache = null;

    /**
     * @var Cache
     */
    private $cacheService;

    /**
     * @var bool
     */
    public $disable_frontedit = false;

    /**
     * @var string Front-edit window size
     */
    public $size = 'large';

    /**
     * @var bool Tell front-edit to treat Bloqs similarly to Grid and Fluid
     */
    public $complex_data_structure = true;

    /**
     * @var HookExecutor
     */
    private $_hookExecutor;

    /**
     * @var FieldTypeManager
     */
    private $_ftManager;

    /**
     * Transfer save data from save() to post_save()
     *
     * @var array
     */
    private static $saveData = [];

    function __construct()
    {
        parent::__construct();

        $this->_hookExecutor = new HookExecutor(ee());

        $filter = new FieldTypeFilter();
        $filter->load(PATH_THIRD.'bloqs/fieldtypes.xml');

        $this->_ftManager = new FieldTypeManager(ee(), $filter, $this->_hookExecutor);

        /** @var \Basee\Cache $cacheService */
        $this->cacheService = ee('bloqs:Cache');

        if (isset(ee()->session)) {
            if (!isset(ee()->session->cache[__CLASS__])) {
                ee()->session->cache[__CLASS__] = [];
            }
            $this->cache =& ee()->session->cache[__CLASS__];

            if (!isset($this->cache['includes'])) {
                $this->cache['includes'] = [];
            }
            if (!isset($this->cache['validation'])) {
                $this->cache['validation'] = [];
            }
            if (!isset($this->cache['grid_assets_loaded'])) {
                $this->cache['grid_assets_loaded'] = [];
            }
        }
    }

    protected function includeThemeJS($file)
    {
        if (!in_array($file, $this->cache['includes'])) {
            $this->cache['includes'][] = $file;
            ee()->cp->add_to_foot('<script type="text/javascript" src="' . $this->getThemeURL() . $file . '?v=' . BLOQS_BUILD_VERSION. '"></script>');
        }
    }

    protected function includeThemeCSS($file)
    {
        if (!in_array($file, $this->cache['includes'])) {
            $this->cache['includes'][] = $file;
            ee()->cp->add_to_head('<link rel="stylesheet" href="' . $this->getThemeURL() . $file . '?v=' . BLOQS_BUILD_VERSION . '">');
        }
    }

    protected function getThemeURL()
    {
        if (!isset($this->cache['theme_url'])) {
            $theme_folder_url = defined('URL_THIRD_THEMES') ? URL_THIRD_THEMES : ee()->config->slash_item('theme_folder_url') . 'third_party/';
            $this->cache['theme_url'] = $theme_folder_url . 'bloqs/';
        }

        return $this->cache['theme_url'];
    }

    protected function includeGridAssets()
    {
        if (!$this->cache['grid_assets_loaded']) {
            ee()->cp->add_js_script('ui', 'sortable');
            ee()->cp->add_js_script('file', 'cp/sort_helper');
            ee()->cp->add_js_script('file', 'cp/grid');
            ee()->cp->add_js_script('plugin', 'nestable');

            $this->cache['grid_assets_loaded'] = true;
        }
    }

    /**
     * Display content in EE6+ Entry Manager. Bloq fields must be set to searchable,
     * and the atoms in a bloq must also be set to searchable to display in the Entry Manager.
     *
     * @param mixed $data
     * @param int $fieldId
     * @param int $entry
     * @return string
     */
    public function renderTableCell($data, $fieldId, $entry)
    {
        if (!$data) {
            return '';
        }

        return str_replace('|', ' ', strip_tags($data));
    }

    /**
     * @param int $fieldId
     * @param int $entryId
     * @return void
     */
    private function checkLog(int $fieldId, int $entryId)
    {
        $result = ee('db')->where([
            'entry_id' => $entryId,
            'field_id' => $fieldId,
        ])->get('blocks_log');

        if ($result->num_rows()) {
            $row = $result->first_row();
            $message = sprintf(
                lang('bloqs_error_criticial_dev_log'),
                $fieldId,
                $entryId,
                $row->updated_at,
                $row->member_id
            );

            //if (App::isAddonInstalled('logit')) {
            //    ee('logit:Message', $message);
            //} else {
            ee()->logger->developer($message, true);
            //}

            $alert = ee('CP/Alert');
            $alert
                ->makeBanner()
                ->asIssue()
                ->withTitle(lang('bloqs_error_critical_title'))
                ->addToBody(lang('bloqs_error_critical_desc'))
                ->canClose()
                ->now();

            ee('db')->delete('blocks_log', [
                'entry_id' => $entryId,
                'field_id' => $fieldId,
            ]);
        }
    }

    /**
     * Display a Bloqs field. If $defaultBlockDefinitions and/or $defaultBlocks are defined, then we're overriding
     * a normal EE field display, and rendering a standalone Bloqs field with the data we want.
     *
     * @param array $data
     * @param array $defaultBlockDefinitions
     * @param array $defaultBlocks
     * @return mixed
     * @throws Exception
     */
    public function display_field($data, array $defaultBlockDefinitions = [], array $defaultBlocks = [])
    {
        /** @var Trial $trialService */
        $trialService = ee('bloqs:Trial');
        if ($trialService->isTrialExpired()) {
            return $trialService->showTrialExpiredInline();
        }

        // Display this on the publish page.
        // Have had a number of support issues b/c people don't see it in the Bloqs CP pages.
        MaxInputVars::checkBanner();

        ee()->benchmark->mark(sprintf('bloqs_display_field_#%d_start', $this->field_id));

        $this->includeGridAssets();
        if (App::isLtEE6()) {
            // Borrow some stuff from EE6 to ease the transition
            $this->includeThemeCSS('css/legacy.css');
        }

        $this->includeThemeCSS('css/cp.css');
        // Newer version of nestedSortable that Structure uses, so we have similar drag/drop behavior
        $this->includeThemeJS('javascript/jquery.mjs.nestedSortable.js');
        $this->includeThemeJS('javascript/tree-validation.js');
        $this->includeThemeJS('javascript/menu-validation.js');
        $this->includeThemeJS('javascript/cp.js');
        $this->includeThemeJS('javascript/clone-events.js');
        $this->includeThemeJS('javascript/fuzzy-filters.js');

        ee()->load->library('logger');
        ee()->lang->loadfile('bloqs');

        $adapter = new Adapter(ee());
        $entryId = $this->content_id ?? 0;
        $autoExpand = get_bool_from_string($this->settings['auto_expand'] ?? 'n');
        $isRevision = ee()->input->get('version') ? true : false;
        $viewData = ['blocks' => []];

        if ($this->_hookExecutor->isActive(HookExecutor::BEFORE_DISPLAY_FIELD)) {
            $viewData = $this->_hookExecutor->beforeDisplayField($entryId, $this->field_id, $viewData);
        }

        $this->checkLog($this->field_id, $entryId);

        $controller = new PublishController(
            ee(),
            $this->id(),
            $this->name(),
            $adapter,
            $this->_ftManager,
            $this->_hookExecutor,
            ee()->logger
        );

        // Validation failed. Either our validation or another validation,
        // we don't know, but now we need to output the data that was
        // entered instead of getting it from the database.
        if (is_array($data) || isset($this->cache['validation'][$this->id()])) {
            if (!is_array($data)) {
                $data = $this->cache['validation'][$this->id()]['value'];
            }

            $viewData = $controller->displayValidatedField(
                $entryId,
                $defaultBlockDefinitions,
                $data,
                $isRevision
            );
        } elseif (!is_array($data)) {
            // Let's build these blocks out
            if ($defaultBlocks) {
                $blocks = $defaultBlocks;
            } else {
                $blocks = $adapter->getBlocks($entryId, $this->field_id);
            }

            $viewData = $controller->displayField(
                $entryId,
                $defaultBlockDefinitions,
                $blocks
            );
        }

        // Patch for EE2 to EE3 migrations
        if (isset($viewData['blocks'])) {
            $viewData['bloqs'] = $viewData['blocks'];
            unset($viewData['blocks']);
        }

        if (AJAX_REQUEST || $autoExpand) {
            foreach ($viewData['bloqs'] as $i => $b) {
                $viewData['bloqs'][$i]['visibility'] = 'expanded';
            }
        }

        $collapseOnDrag = bool_config_item('bloqs_collapse_on_drag');
        $confirmBloqRemoval = bool_config_item('bloqs_confirm_bloq_removal');
        $enableStrictTreeStructure = bool_config_item('bloqs_enable_strict_tree_structure');

        // Used for confirm removing cloneable bloqs, and any bloq in general if the global $confirmBloqRemoval is enabled.
        // Regardless of global setting, if we have cloneable bloqs in a component, we need to confirm removal b/c
        // the user can only remove all but the last of a cloneable type.
        ee()->cp->add_js_script(['file' => ['cp/confirm_remove']]);
        $modalController = new ModalController();
        $modalController->create('modal-confirm-remove-bloq', 'ee:_shared/modal_confirm_remove', [
            'form_url' => '',
            'checklist' => []
        ]);

        ee()->javascript->set_global([
            // collapseOnDrag is an un-supported hidden config. Use at your own risk.
            'bloqs.collapseOnDrag' => $collapseOnDrag,
            'bloqs.confirmBloqRemoval' => $confirmBloqRemoval,
            'bloqs.strictTreeStructure' => $enableStrictTreeStructure,
            'bloqs.eeVersion' => App::majorVersion(),
            'bloqs.isModernEE' => App::isGteEE6(),
            'bloqs.isLegacyEE' => App::isLtEE6(),
            'bloqs.btn_confirm_and_remove' => lang('btn_confirm_and_remove'),
            'bloqs.btn_close' => lang('close'),
            'bloqs.confirm_removal_desc' => lang('confirm_removal_desc'),
            'bloqs.confirm_removal_cloneable_desc' => lang('bloqs_confirm_removal_cloneable_desc'),
        ]);

        /** @var FormSecret $secretService */
        $secretService = ee('bloqs:FormSecret');
        $secretService
            ->setEntryId($entryId)
            ->setFieldId($this->field_id)
            ->setSecret();

        $viewData['blockDefinitions'] = $this->groupBlockDefinitions($viewData['blockDefinitions']);

        $viewData = array_merge($viewData, [
            'blockGroups' => $adapter->getBlockGroupsDictionary(),
            'confirmBloqRemoval' => $confirmBloqRemoval,
            'eeVersion' => 'ee'.App::majorVersion(),
            'eeVersionNumber' => App::majorVersion(),
            'isModernEE' => App::isGteEE6(),
            'isLegacyEE' => App::isLtEE6(),
            'fieldSettingNestable' => $this->settings['nestable'] ?? 'n',
            'formSecret' => $secretService->getSecret(),
            'formSecretFieldName' => $secretService->getKeyName(),
            'menuGridDisplay' => isset($this->settings['menu_grid_display']) && $this->settings['menu_grid_display'] === 'y',
            'showEmpty' => empty($viewData['bloqs']),
            'isComponentBuilder' => isset($this->settings['component_builder']) && $this->settings['component_builder'] === 'y',
        ]);

        if ($this->_hookExecutor->isActive(HookExecutor::AFTER_DISPLAY_FIELD)) {
            $viewData = $this->_hookExecutor->afterDisplayField($entryId, $this->field_id, $viewData);
        }

        $view = ee('View')->make($this->pkg.':editor')->render($viewData);

        ee()->benchmark->mark(sprintf('bloqs_display_field_#%d_end', $this->field_id));

        return $view;
    }

    /**
     * Group the definitions by groupId for presentation in the view file.
     *
     * @param array $blockDefinitions
     * @return array
     */
    private function groupBlockDefinitions(array $blockDefinitions = []): array
    {
        $grouped = [];

        foreach ($blockDefinitions as $blockDefinition) {
            $grouped[$blockDefinition['definition']->getGroupId()][] = $blockDefinition;
        }

        return $grouped;
    }

    /**
     * @param $data
     * @return bool
     */
    public function validate($data)
    {
        $fieldId = $this->id();
        if (isset($this->cache['validation'][$fieldId])) {
            return $this->cache['validation'][$fieldId];
        }

        ee()->load->library('logger');
        ee()->lang->loadfile('bloqs');

        $adapter = new Adapter(ee());
        $entryId = $this->settings['entry_id'] ?? ee()->input->get_post('entry_id');

        $controller = new PublishController(
            ee(),
            $this->id(),
            $this->name(),
            $adapter,
            $this->_ftManager,
            $this->_hookExecutor,
            ee()->logger
        );

        $validated = $controller->validate(
            $data,
            $entryId
        );

        $this->cache['validation'][$fieldId] = $validated;

        return $validated;
    }

    /**
     * If multiple Bloqs fields are on the page at the same time, create a key with the field's ID and save its
     * data to that key. In very rare instances (causes unknown), EE seems to confuse the order of which field to save.
     * If 2 Bloqs fields exist, EE creates an instance of this class for each field, then it'll call save(), save(),
     * then post_save() post_save(), which is expected. However, in rare instances it would reference the class instance
     * of the 2nd field when saving the first, thus $this->save would contain data from field #2, when trying to
     * save field #1. (╯°□°）╯︵ ┻━┻ Using a static property with a key for each field seems to prevent this because
     * regardless of the call order, and number of Bloqs fields in the entry, it will always reference by ID.
     *
     * @param mixed $data
     * @return string
     */
    public function save($data)
    {
        self::$saveData[$this->field_id] = $data;

        // Something in EE changed since LivePreview support was initially added to Bloqs.
        // ee('LivePreview')->getEntryData() and hasEntryData() are both false at this point,
        // but unless we return $data Relationships do not work in previews.
        // I'm pretty sure it's this commit, which introduces calling save() functions on FTs.
        // https://github.com/ExpressionEngine/ExpressionEngine/commit/007ae46998aef84a1d435e712c420739fcde46a9
        // This method has been returning the blank string since 2016, and I'm unsure of what repercussions
        // there could be changing that now, so to play it safe, we're doing this extra check :/
        if (App::isLivePreviewRequest()) {
            return $data;
        }

        return ' ';
    }

    /**
     * @param int|null $fieldId
     * @return bool
     */
    private function shouldSave($fieldId)
    {
        if (!$fieldId) {
            return false;
        }

        return is_array(self::$saveData[$fieldId]) && !empty(self::$saveData[$fieldId]);
    }

    /**
     * Prevent possible double save attempts if Publisher is also installed.
     * If the save action is triggered twice it will likely result in lost content
     * in the Bloqs when Publisher has to handle deleting and re-creating blocks.
     *
     * @return bool
     */
    private function validateSecretKey(): bool
    {
        // Disabled, again MKTWEB-6312
        return true;

        if (!App::isAddonInstalled('publisher')) {
            return true;
        }

        $entryId = $this->content_id ?? 0;

        /** @var FormSecret $secretService */
        $secretService = ee('bloqs:FormSecret');
        $secretService
            ->setEntryId($entryId)
            ->setFieldId($this->field_id);

        if (!$secretService->isSecretValid()) {
            $message = sprintf(
                lang('bloqs_invalid_secret_key'),
                $this->id(),
                $entryId,
                App::userData('member_id')
            );

            //if (App::isAddonInstalled('logit')) {
            //    ee('logit:Message', $message);
            //} else {
                ee()->logger->developer($message);
            //}

            $alert = ee('CP/Alert');
            $alert
                ->makeBanner()
                ->asIssue()
                ->withTitle(lang('bloqs_error_generic_title'))
                ->addToBody(lang('bloqs_error_generic_desc') . ' ' . lang('bloqs_error_desc_more'))
                ->canClose()
                ->defer();

            return false;
        }

        return true;
    }

    /**
     * @param string $data
     */
    public function post_save($data)
    {
        ee()->load->library('logger');
        ee()->lang->loadfile('bloqs');
        $fieldId = $this->field_id ?? null;
        $fieldName = $this->field_name ?? null;

        if (!$this->validateSecretKey()) {
            return;
        }

        // Prevent saving if save() was never called, happens in Channel Form if the field is missing from the form,
        // or if the field is conditionally hidden.
        if ($this->shouldSave($fieldId)) {
            try {
                $adapter = new Adapter(ee());
                $entryId = $this->content_id ?? ee()->input->get_post('entry_id');
                // Capture current block IDs before we take any possible action on them.
                $blockIds = $adapter->getBlockIds($entryId, $fieldId);
                $isRevision = App::isRevisionSaveRequest();
                $isClone = App::isCloningRequest();
                $saveData = self::$saveData[$fieldId];

                $controller = new PublishController(
                    ee(),
                    $fieldId,
                    $fieldName,
                    $adapter,
                    $this->_ftManager,
                    $this->_hookExecutor,
                    ee()->logger
                );

                // Decode the string that the Nestable plugin creates and pass it along
                $fieldPostData = ee()->input->post('field_id_' . $fieldId, true) ?: [];
                if (!isset($saveData['tree_order']) && isset($fieldPostData['tree_order'])) {
                    $saveData['tree_order'] = json_decode($fieldPostData['tree_order'], true);
                }

                $controller->save(
                    $saveData,
                    $entryId,
                    $isRevision,
                    $isClone
                );

                if ($this->cacheService->isCacheEnabled()) {
                    // Clear the cache for this entry and field
                    $cacheKey = implode('/', [$entryId, $fieldId]);
                    $this->cacheService->delete($cacheKey);
                }

                // When viewing a revision the blocks are added to the page as if they were new blocks, however, saving
                // the revision as the new version of the entry will append them to the blocks currently assigned to the
                // entry, thus we delete all the old blocks b/c the revision data will be used to re-create them.
                if (!empty($blockIds) && ($isRevision || $isClone)) {
                    foreach ($blockIds as $blockId) {
                        $adapter->deleteBlock($blockId);
                    }
                }
            } catch (Exception $exception) {
                ee()->logger->developer('Bloqs save error: '. $exception->getMessage() . $exception->getTraceAsString());

                $alert = ee('CP/Alert');
                $alert
                    ->makeBanner()
                    ->asIssue()
                    ->withTitle(lang('bloqs_error_generic_title'))
                    ->addToBody(lang('bloqs_error_generic_desc'))
                    ->canClose()
                    ->defer();
            }
        }
    }

    /**
     * @param $entryId
     * @param $fieldId
     * @return array
     */
    private function getBlocks($entryId, $fieldId, array $params = [])
    {
        $key = json_encode([
            'bloqs' => 'fetch' ,
            'entryId' => $entryId,
            'fieldId' => $fieldId,
            'params' => $params,
        ]);
        $blocks = $this->cache[$key] ?? null;

        if ($blocks) {
            ee()->TMPL->log_item('[Bloqs] retrieved cached blocks for "' . $key . '"');
            return $blocks;
        }

        ee()->TMPL->log_item('[Bloqs] fetching blocks for "' . $key . '"');

        $adapter = new Adapter(ee());
        $blocks = $adapter->getBlocks(
            $entryId,
            $fieldId
        );

        $this->cache[$key] = $blocks;

        return $blocks;
    }

    /**
     * @param array $data
     * @param array $params
     * @param bool  $tagdata
     * @return string
     * @throws Exception
     */
    public function replace_tag($data, $params = [], $tagdata = false)
    {
        ee()->benchmark->mark(sprintf('bloqs_render_field_#%s_start', $this->field_id));

        if (!$tagdata) {
            return '';
        }

        ee()->lang->loadfile('bloqs');

        /** @var Trial $trialService */
        $trialService = ee('bloqs:Trial');
        if ($trialService->isTrialExpired()) {
            return $trialService->showTrialExpiredInline();
        }

        $entryId = $this->row['entry_id'];

        if ($this->cacheService->isCacheEnabled($params)) {
            $cacheKey = implode('/', array_merge([$entryId, $this->field_id, 'replaceTag'], $params));
            $cacheItem = $this->cacheService->get($cacheKey);

            if ($cacheItem) {
                ee()->benchmark->mark(sprintf('bloqs_render_field_#%d_end', $this->field_id));

                return '<!-- Start cached Bloqs output -->' . $cacheItem .'<!-- End cached Bloqs output -->';
            }
        }

        $adapter = new Adapter(ee());

        // Before we attempt to call the ee:LivePreview service lets make sure its available.
        $isLivePreviewAvailable = App::isFeatureAvailable('livePreview');

        // @todo Temporary, move this to Publisher as a hook
        $previewDiff =
            array_key_exists('search:publisher_open', $params)
            && ee('Request')->post('publisher_view_status') === 'draft';

        if (
            $isLivePreviewAvailable
            && ee('LivePreview')->hasEntryData()
            && isset($_POST['field_id_'.$this->field_id])
            && !$previewDiff
        ) {
            $entryData = ee('LivePreview')->getEntryData();

            $blocks = $adapter->getBlocksFromPost(
                $_POST['field_id_'.$this->field_id],
                $this->field_id,
                $this->content_id,
                $entryData['entry_id'],
            );
        } else {
            $blocks = $this->getBlocks($entryId, $this->field_id, $params);
        }

        $controller = new TagController(
            ee(),
            $this->field_id,
            $this->_ftManager,
            $this->settings,
            $this->_hookExecutor
        );

        $result = $controller->replace($tagdata, $blocks, $this->row, $params);

        if ($this->cacheService->isCacheEnabled($params)) {
            $this->cacheService->save($cacheKey, $result, $this->cacheService->getCacheLifetime());
        }

        if (App::userData('can_access_cp') && strstr($tagdata, '{close:') !== false) {
            ee()->load->library('logger');
            ee()->logger->developer(lang('bloqs_deprecated_close_tags'), true);
        }

        ee()->benchmark->mark(sprintf('bloqs_render_field_#%d_end', $this->field_id));

        return $result;
    }

    /**
     * @param $data
     * @param array $params
     * @param bool $tagdata
     * @param string $modifier
     * @return int|string
     */
    public function replace_tag_catchall($data, $params = [], $tagdata = false, $modifier = '')
    {
        $entryId = $this->row['entry_id'];

        if ($this->cacheService->isCacheEnabled($params)) {
            $cacheKey = implode('/', array_merge([$entryId, $this->field_id, 'replaceTagCatchAll'], $params));
            $cacheItem = $this->cacheService->get($cacheKey);

            if ($cacheItem) {
                return '<!-- Start cached Bloqs output -->' . $cacheItem .'<!-- End cached Bloqs output -->';
            }
        }

        $blocks = $this->getBlocks($entryId, $this->field_id);

        $controller = new TagController(
            ee(),
            $this->field_id,
            $this->_ftManager,
            $this->settings,
            $this->_hookExecutor
        );

        $result = '';

        if (in_array($modifier, ['total_blocks', 'total_bloqs', 'total_rows'])) {
            $result = $controller->totalBlocks($blocks, $params);
        }

        if ($this->cacheService->isCacheEnabled($params)) {
            $this->cacheService->save($cacheKey, $result, $this->cacheService->getCacheLifetime());
        }

        return $result;
    }

    /**
     * @param $data
     * @return array
     */
    public function display_settings($data)
    {
        $this->includeThemeCSS('css/edit-field.css');
        $this->includeThemeJS('javascript/edit-field.js');

        $blockDefinitionMaintenanceUrl = ee('CP/URL')->make('addons/settings/'.$this->pkg);

        ee()->lang->loadfile('bloqs');

        $adapter = new Adapter(ee());
        $blockGroups = $adapter->getBlockGroupsDictionary();
        $selectedBlockDefinitions = $this->field_id ? $adapter->getBlockDefinitionsForField($this->field_id) : [];
        $usedBlockDefinitions = $this->field_id && !bool_config_item('bloqs_disable_usage_reports') ? $adapter->getBlockDefinitionsUsageByFieldId($this->field_id) : [];
        $fieldSettings = $data['field_settings'] ?? [];
        $allBlockDefinitions = $adapter->getBlockDefinitions();
        $blockDefinitionDisplayOrder = $fieldSettings['blockDefinitions'] ?? [];
        $blockDefinitions = $this->sortDefinitions($selectedBlockDefinitions, $allBlockDefinitions, $blockDefinitionDisplayOrder);
        $blockDefinitionChoices = [];
        $blockDefinitionChoicesDisabled = [];
        $blockDefinitionChoicesSelected = [];

        /** @var \BoldMinded\Bloqs\Entity\BlockDefinition $blockDefinition */
        foreach ($blockDefinitions as $blockDefinition) {
            $componentIcon = $blockDefinition->isComponent() ? ' <span class="fas fa-layer-group" style="color: var(--ee-accent-dark);" title="Component"></span>' : '';
            $deprecatedNote = $blockDefinition->getDeprecatedNote() ? 'Deprecated: ' . $blockDefinition->getDeprecatedNote() : 'Deprecated';
            $deprecatedIcon = $blockDefinition->isDeprecated() ? '<span class="fas fa-fw fa-exclamation-circle" style="color: var(--ee-warning);" title="'. $deprecatedNote .'"></span>' : '';
            $displayLabel = App::isLtEE6() ?
                $blockDefinition->getName() :
                sprintf('<span data-field-name="%s">%s%s%s</span>', $blockDefinition->getShortName(), $blockDefinition->getName(), $componentIcon, $deprecatedIcon);
            $blockUsageCount = isset($usedBlockDefinitions[$blockDefinition->getId()]) && !empty($usedBlockDefinitions[$blockDefinition->getId()]) ? count($usedBlockDefinitions[$blockDefinition->getId()]) : 0;
            $blockUsageUrl = ee('CP/URL')->make('addons/settings/bloqs/block-usage', ['definitionId' => $blockDefinition->getId()])->compile();
            $isUsed = array_key_exists($blockDefinition->getId(), $usedBlockDefinitions);
            $isSelected = $blockDefinition->selected;
            $isDisabled = !bool_config_item('bloqs_disable_strict_assignment') && ($isSelected && $isUsed);
            $instructions = '';

            if (!empty($blockGroups) && isset($blockGroups[$blockDefinition->getGroupId()])) {
                if (App::isLtEE6()) {
                    $instructions .= sprintf(' (%s)', $blockGroups[$blockDefinition->getGroupId()]);
                } else {
                    $displayLabel .= sprintf(' <span class="blockselector__group">(%s)</span>', $blockGroups[$blockDefinition->getGroupId()]);
                }
            }

            if ($blockUsageCount && !bool_config_item('bloqs_disable_usage_reports')) {
                if (App::isLtEE6()) {
                    $instructions .= sprintf(' Used in %d entries', $blockUsageCount);
                } else {
                    $displayLabel .= sprintf(' <a href="%s" class="blockselector__usage">Used in %d entries</a>', $blockUsageUrl, $blockUsageCount);
                }
            }

            if ($isDisabled) {
                $blockDefinitionChoicesDisabled[] = $blockDefinition->getId();
            }

            if ($isSelected) {
                $blockDefinitionChoicesSelected[] = $blockDefinition->getId();
            }

            $blockDefinitionChoices[$blockDefinition->getId()] = [
                'instructions' => $instructions,
                'label' => $displayLabel,
            ];
        }

        $blocksOutput = ee('View')->make('ee:_shared/form/fields/select')->render([
            'field_name' => 'blockDefinitions',
            'force_react' => true,
            'choices' => $blockDefinitionChoices,
            'disabled_choices' => $blockDefinitionChoicesDisabled,
            'value' => $blockDefinitionChoicesSelected,
            'multi' => true,
            'reorderable' => true,
            'no_results' => ['text' => lang('bloqs_fieldsettings_no_blocks_defined')],
        ]);

        $blocksOutput .= "<p><a class='btn action button button--secondary button--small' href='{$blockDefinitionMaintenanceUrl}'>" . lang('bloqs_fieldsettings_manage_block_definitions') . "</a></p>";

        if ($this->field_id) {
            $templateCodeOutput = 'Loading...';
        } else {
            $templateCodeOutput = 'You must save this field before a basic component can be provided.';
        }

        $templateCodeDescription = lang('bloqs_fieldsettings_template_code_desc');

        $urlHelper = new UrlHelper();

        if (App::isFeatureAvailable('generators')) {
            ee()->javascript->set_global('bloqs.ajax_fetch_template_code',
                ee('CP/URL')->make('cp/design/copy/fields/' . $this->field_id)->compile()
            );

            $templateCodeDescription .= ' ' .lang('bloqs_fieldsettings_template_code_desc_generators');
        } else {
            ee()->javascript->set_global('bloqs.ajax_fetch_template_code',
                $urlHelper->getAction('fetch_template_code', [
                    'field_id' => $this->field_id,
                ])
            );
        }

        $settings = [
            [
                'title' => 'bloqs_fieldsettings_auto_expand',
                'desc' => 'bloqs_fieldsettings_auto_expand_desc',
                'wide' => true,
                'fields' => [
                    'auto_expand' => [
                        'type' => 'yes_no',
                        'value' => (isset($fieldSettings['auto_expand']) ? $fieldSettings['auto_expand'] : 'n'),
                    ]
                ]
            ],
            [
                'title' => 'bloqs_fieldsettings_nestable',
                'desc' => 'bloqs_fieldsettings_nestable_desc',
                'wide' => true,
                'fields' => [
                    'nestable' => [
                        'type' => 'yes_no',
                        'value' => (isset($fieldSettings['nestable']) ? $fieldSettings['nestable'] : 'n'),
                    ]
                ]
            ],
            [
                'title' => 'bloqs_fieldsettings_menu_grid_display',
                'desc' => 'bloqs_fieldsettings_menu_grid_display_desc',
                'wide' => true,
                'fields' => [
                    'menu_grid_display' => [
                        'type' => 'yes_no',
                        'value' => (isset($fieldSettings['menu_grid_display']) ? $fieldSettings['menu_grid_display'] : ''),
                    ]
                ]
            ],
            [
                'title' => 'bloqs_fieldsettings_associateblocks',
                'desc' => lang('bloqs_fieldsettings_associateblocks_desc') . (!bool_config_item('bloqs_disable_strict_assignment') ? lang('bloqs_fieldsettings_associateblocks_desc_continued') : ''),
                'wide' => true,
                'fields' => [
                    'blockDefinitions' => [
                        'type' => 'html',
                        'content' => $blocksOutput,
                    ]
                ]
            ],
            [
                'title' => 'bloqs_fieldsettings_template_code',
                'desc' => $templateCodeDescription,
                'wide' => true,
                'fields' => [
                    'template' => [
                        'type' => 'textarea',
                        'attrs' => 'class="template-edit" readonly',
                        'value' => $templateCodeOutput,
                    ]
                ]
            ],
        ];

        return [
            'field_options_bloqs' => [
                'label' => 'field_options',
                'group' => 'bloqs',
                'settings' => $settings
            ]
        ];
    }

    /**
     * @param $data
     * @return array
     */
    public function save_settings($data)
    {
        // Don't perform any actions when EE is validating the ft settings form
        if (AJAX_REQUEST && ee()->input->post('ee_fv_field')) {
            return array_merge($data, ['field_wide' => true]);
        }

        //there are a few fields that are passed in with the data array
        //that we don't want to save - so we strip those out of the data array
        foreach (['field_name', 'field_id', 'field_required'] as $field) {
            if (isset($data[$field])) {
                unset($data[$field]);
            }
        }

        // Check to see if any of the associated block definitions to this field
        // are a component, and if so force the field to be nestable, otherwise
        // the field will not render or save correctly in the CP.
        $adapter = new Adapter(ee());
        $data['blockDefinitions'] = $this->getAssociatedDefinitions();
        $hasComponents = false;

        foreach ($data['blockDefinitions'] as $blockDefinitionId) {
            $blockDefinition = $adapter->getBlockDefinitionById($blockDefinitionId);

            if ($blockDefinition->isComponent()) {
                $hasComponents = true;
            }
        }

        if ($hasComponents) {
            $data['nestable'] = 'y';
        }

        unset($data['template']);

        return array_merge($data, ['field_wide' => true]);
    }

    /**
     * @param array $data
     * @throws Exception
     */
    public function post_save_settings($data = [])
    {
        // Don't perform any actions when EE is validating the ft settings form
        if (AJAX_REQUEST && ee()->input->post('ee_fv_field')) {
            return;
        }

        $adapter = new Adapter(ee());
        $fieldId = intval($data['field_id']);

        foreach ($this->getAssociatedDefinitions() as $order => $blockDefinitionId) {
            $adapter->associateBlockDefinitionWithField(
                $fieldId,
                intval($blockDefinitionId),
                $order
            );
        }

        foreach ($this->getDisassociatedDefinitions() as $order => $blockDefinitionId) {
            $adapter->disassociateBlockDefinitionWithField(
                $fieldId,
                intval($blockDefinitionId)
            );
        }
    }

    /**
     * @return array
     */
    private function getAssociatedDefinitions(): array
    {
        $postData = ee()->input->post('blockDefinitions');

        if (!$postData) {
            return [];
        }

        $blockDefinitions = array_reverse($postData);
        $collection = [];

        if (is_array($blockDefinitions) && !empty($blockDefinitions)) {
            foreach (array_filter($blockDefinitions) as $order => $blockDefinitionId) {
                $collection[$order] = $blockDefinitionId;
            }
        }

        return $collection;
    }

    /**
     * @return array
     */
    private function getDisassociatedDefinitions(): array
    {
        $adapter = new Adapter(ee());
        $blockDefinitionsDisassociate = ee()->input->post('blockDefinitions_disassociate');
        $collection = [];

        if (is_array($blockDefinitionsDisassociate) && !empty($blockDefinitionsDisassociate)) {
            foreach (array_filter($blockDefinitionsDisassociate) as $order => $blockDefinitionId) {
                // If an unchecked block is assigned to a component, don't delete it's association.
                // This lets us create a component with child blocks, add the component to an entry,
                // but when editing the field settings we can leave the block's checkbox unchecked,
                // thus it will not appear in the menu. Basically allowing a block to only exist as
                // a child of a component bloq. Once the block becomes checked and is used by an entry
                // even if it is assigned to a component it will now appear in the menu and become
                // disabled because it is being used, and we want to prevent data loss.
                $componentDefinitionUsage = $adapter->getBlocksUsedInComponentByDefinitionId($blockDefinitionId);
                if (empty($componentDefinitionUsage)) {
                    $collection[$order] = $blockDefinitionId;
                }
            }
        }

        return $collection;
    }

    /**
     * @param array $selected
     * @param array $all
     * @param array $displayOrder
     * @return array
     */
    protected function sortDefinitions(array $selected = [], array $all = [], array $displayOrder = [])
    {
        $return = [];
        $selectedIds = [];

        /** @var BlockDefinition $definition */
        foreach ($selected as $definition) {
            $selectedIds[] = $definition->getId();
            $definition->selected = true;
            $return[] = $definition;
        }

        foreach ($all as $definition) {
            if (in_array($definition->getId(), $selectedIds)) {
                continue;
            }

            $definition->selected = false;
            $return[] = $definition;
        }

        // If nothing is selected, there is nothing to sort.
        if (!empty($return) && !empty($displayOrder)) {
            $return = $this->sortArrayByArray($return, $displayOrder);
        }

        return $return;
    }

    /**
     * @param array $toSort
     * @param array $sortBy
     * @return array
     */
    private function sortArrayByArray(array $toSort, array $sortBy)
    {
        $ordered = [];
        $keys = array_flip(array_column($toSort, 'id'));

        foreach($sortBy as $key) {
            // Older Bloqs installs might not have the proper order data, so skip and so the page loads.
            // Once the field is saved again it'll have the correct order data.
            // https://boldminded.com/support/ticket/2762
            if (is_array($key)) {
                continue;
            }
            if(array_key_exists($key, $keys) && isset($toSort[$keys[$key]])) {
                $ordered[$key] = $toSort[$keys[$key]];
            }
        }

        foreach ($toSort as $unsorted) {
            if (!array_key_exists($unsorted->getId(), $ordered)) {
                $ordered[$unsorted->getId()] = $unsorted;
            }
        }

        return $ordered;
    }
}
