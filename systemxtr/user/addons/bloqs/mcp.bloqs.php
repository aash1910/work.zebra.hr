<?php

use BoldMinded\Bloqs\Library\Basee\App;
use BoldMinded\Bloqs\Controller\FieldTypeFilter;
use BoldMinded\Bloqs\Controller\FieldTypeManager;
use BoldMinded\Bloqs\Controller\HookExecutor;
use BoldMinded\Bloqs\Controller\TemplateCodeRenderer;
use BoldMinded\Bloqs\Database\Adapter;
use BoldMinded\Bloqs\Library\Basee\Version;
use BoldMinded\Bloqs\Entity\AtomDefinition;
use BoldMinded\Bloqs\Entity\Block;
use BoldMinded\Bloqs\Entity\BlockDefinition;
use BoldMinded\Bloqs\Entity\BlockGroup;
use BoldMinded\Bloqs\Entity\FieldType;
use BoldMinded\Bloqs\Library\FileField\FileField;
use BoldMinded\Bloqs\Service\MaxInputVars;
use BoldMinded\Bloqs\Service\StandaloneField;
use ExpressionEngine\Service\Sidebar\FolderItem;
use ExpressionEngine\Service\Sidebar\Sidebar;

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

class Bloqs_mcp extends Bloqs_base
{
    /**
     * @var Adapter
     */
    private $adapter;

    /**
     * @var HookExecutor
     */
    private $_hookExecutor;

    /**
     * @var FieldTypeManager
     */
    private $_ftManager;

    public function __construct()
    {
        parent::__construct();

        $this->adapter = new Adapter(ee());
        $this->_hookExecutor = new HookExecutor(ee());

        $filter = new FieldTypeFilter();
        $filter->load(PATH_THIRD . 'bloqs/fieldtypes.xml');

        $this->_ftManager = new FieldTypeManager(ee(), $filter, $this->_hookExecutor);
    }

    private function generateSidebar()
    {
        MaxInputVars::checkInline();

        $blockDefinitionId = ee()->input->get('definitionId');
        $groupId = ee()->input->get('groupId');
        $lastSegment = end(ee()->uri->rsegments);

        /** @var Sidebar $sidebar */
        $sidebar = ee('CP/Sidebar')->make();

        $heading = $sidebar->addHeader(lang('bloqs_blockdefinitions_title'), $this->makeCpUrl('index'));
        $heading->withButton(lang('new'), $this->makeCpUrl('block-definition', ['definitionId' => 'new']));

        if (in_array($lastSegment, ['index', 'block-definition', 'bloqs']) && !$blockDefinitionId) {
            $heading->isActive();
        }

        if (in_array($lastSegment, ['block-definition'])) {
            $blockDefinitions = $this->adapter->getBlockDefinitions();
            $list = $heading->addBasicList();

            /** @var BlockDefinition $blockDefinition */
            foreach ($blockDefinitions as $blockDefinition) {
                //$componentIcon = $blockDefinition->isComponent() ? ' <span class="fas fa-layer-group" title="Component"></span>' : '';

                /** @var FolderItem $item */
                $url = $this->makeCpUrl('block-definition', ['definitionId' => $blockDefinition->getId()]);
                $item = $list->addItem($blockDefinition->getName(), $url);

                if ($blockDefinition->getId() == $blockDefinitionId) {
                    $item->isActive();
                }
            }
        }

        $heading = $sidebar->addHeader(lang('bloqs_block_groups_title'), $this->makeCpUrl('block-groups'));
        $heading->withButton(lang('new'), $this->makeCpUrl('block-group', ['groupId' => 'new']));

        if (in_array($lastSegment, ['block-group', 'block-groups']) && !$groupId) {
            $heading->isActive();
        }

        if (in_array($lastSegment, ['block-group'])) {
            $blockGroups = $this->adapter->getBlockGroups();

            $list = $heading->addBasicList();

            /** @var BlockGroup $blockDefinition */
            foreach ($blockGroups as $blockGroup) {
                /** @var FolderItem $item */
                $url = $this->makeCpUrl('block-group', ['groupId' => $blockGroup->getId()]);
                $item = $list->addItem($blockGroup->getName(), $url);

                if ($blockGroup->getId() == $groupId) {
                    $item->isActive();
                }
            }
        }

        $releaseNotes = $sidebar->addHeader(lang('bloqs_release_notes'), $this->makeCpUrl('release-notes'));

        if (end(ee()->uri->rsegments) === 'release-notes') {
            $releaseNotes->isActive();
        }
    }

    /**
     * @return array
     */
    public function index()
    {
        ee()->view->header = ['title' => lang('bloqs_blockdefinitions_title')];

        $this->generateSidebar();

        $disableUsageReports = bool_config_item('bloqs_disable_usage_reports');

        $vars['blockDefinitions'] = $this->adapter->getBlockDefinitions();
        if (!$disableUsageReports) {
            $vars['blockDefinitionsUsage'] = $this->adapter->getBlockDefinitionsUsage();
            $vars['blockComponentsUsage'] = $this->adapter->getBlocksUsedInComponents();
        }
        $vars['blockDefinitionUrl'] = $this->makeCpUrl('block-definition', ['definitionId' => 'new']);
        $vars['confirmDeleteBlockUrl'] = $this->makeCpUrl('confirm-delete', ['definitionId' => '']);
        $vars['copyBlockUrl'] = $this->makeCpUrl('copy-block', ['definitionId' => '']);
        $vars['disableUsageReports'] = $disableUsageReports;

        // Handle the delete functionality in the Add-on Manager view.
        ee()->javascript->output('
             $("a.m-link").click(function (e) {
                var modalIs = $("." + $(this).attr("rel"));
                $(".checklist", modalIs)
                  .html("") // Reset it
                  .append("<li>" + $(this).data("confirm") + "</li>");
                $("input[name=\'definitionId\']", modalIs).val($(this).data("definitionId"));
                e.preventDefault();
              });
            ');

        return $this->renderView('block-definitions', $vars);
    }

    public function blockUsage()
    {
        ee()->lang->load('admin');
        ee()->view->header = ['title' => lang('bloqs_block_usage_title')];

        $this->generateSidebar();

        $blockDefinitionId = intval(ee()->input->get_post('definitionId'));
        $blockDefinitionsUsage = [];
        $componentDefinitionsUsage = [];
        $entryIds = [];
        $components = [];

        if (!bool_config_item('bloqs_disable_usage_reports')) {
            $blockDefinitionsUsage = $this->adapter->getBlockDefinitionsUsage();
            $componentDefinitionsUsage = $this->adapter->getBlocksUsedInComponentByDefinitionId($blockDefinitionId);
        }

        if (isset($blockDefinitionsUsage[$blockDefinitionId])) {
            $entryIds = $blockDefinitionsUsage[$blockDefinitionId];
        }

        if (isset($componentDefinitionsUsage[$blockDefinitionId])) {
            foreach ($componentDefinitionsUsage[$blockDefinitionId] as $definitionId) {
                $components[] = $this->adapter->getBlockDefinitionById($definitionId);
            }
        }

        $vars = [
            'blockDefinition' => $this->adapter->getBlockDefinitionById($blockDefinitionId),
            'entries' => ee('Model')->get('ChannelEntry', $entryIds)->all(),
            'components' => $components,
            'disableUsageReports' => bool_config_item('bloqs_disable_usage_reports')
        ];

        return $this->renderView('block-usage', $vars);
    }

    public function blockGroups()
    {
        ee()->view->header = ['title' => lang('bloqs_block_groups_title')];

        $this->generateSidebar();

        $vars['blockGroups'] = $this->adapter->getBlockGroups();
        $vars['blockGroupUrl'] = $this->makeCpUrl('block-group', ['groupId' => 'new']);
        $vars['confirmDeleteGroupUrl'] = $this->makeCpUrl('confirm-delete-group', ['groupId' => '']);

        // Handle the delete functionality in the Add-on Manager view.
        ee()->javascript->output('
             $("a.m-link").click(function (e) {
                var modalIs = $("." + $(this).attr("rel"));
                $(".checklist", modalIs)
                  .html("") // Reset it
                  .append("<li>" + $(this).data("confirm") + "</li>");
                $("input[name=\'definitionId\']", modalIs).val($(this).data("definitionId"));
                e.preventDefault();
              });
            ');

        return $this->renderView('block-groups', $vars, [
            $this->makeCpUrl('block-groups') => lang('bloqs_block_groups_title'),
        ]);
    }

    public function blockGroup()
    {
        // Load native fields language files
        ee()->lang->loadfile('fieldtypes');
        ee()->lang->loadfile('admin_content');
        ee()->lang->loadfile('channel');
        ee()->load->library('form_validation');

        $blockGroupId = ee()->input->get_post('groupId');

        if ($blockGroupId === 'new') {
            $blockGroupId = null;
            $blockGroup = new BlockGroup();
            $blockGroup
                ->setId(0)
                ->setName('')
                ->setOrder(0)
            ;
        } else {
            $blockGroupId = intval($blockGroupId);
            $blockGroup = $this->adapter->getBlockGroupById($blockGroupId);
        }

        $vars = [];

        $sections = [
            [
                [
                    'title' => 'bloqs_blockgroup_name',
                    'desc' => lang('bloqs_blockgroup_name_info'),
                    'fields' => [
                        'blockgroup_name' => [
                            'required' => true,
                            'type' => 'text',
                            'value' => $blockGroup->getName(),
                        ]
                    ]
                ],
                [
                    'title' => 'bloqs_blockgroup_order',
                    'desc' => lang('bloqs_blockgroup_order_info'),
                    'fields' => [
                        'blockgroup_order' => [
                            'required' => true,
                            'type' => 'text',
                            'value' => $blockGroup->getOrder(),
                        ]
                    ]
                ],
            ],
        ];

        $errors = [];

        if (!empty($_POST)) {
            ee()->form_validation->setCallbackObject($blockGroup);
            ee()->form_validation->set_rules('blockgroup_name', 'Name', 'trim|required');
            ee()->form_validation->set_rules('blockgroup_order', 'Order', 'trim|required|numeric');
            $is_valid = ee()->form_validation->run();

            if ($is_valid === false) {
                $this->addAlert(false, 'blocks_settings_alert', lang('bloqs_blockgroup_alert_title'), lang('bloqs_blockgroup_alert_message'));
            } else {
                $name = ee()->input->post('blockgroup_name');
                $order = ee()->input->post('blockgroup_order');

                if (empty($errors)) {
                    $blockGroup->setName($name);
                    $blockGroup->setOrder($order);

                    if ($blockGroupId == null) {
                        $this->adapter->createBlockGroup($blockGroup);
                    } else {
                        $this->adapter->updateBlockGroup($blockGroup);
                    }

                    $this->addAlert(true, 'blocks_settings_alert', lang('bloqs_blockgroup_success_title'));

                    ee()->functions->redirect($this->makeCpUrl('block-groups'));
                    return;
                }
            }
        }

        //Page Specific Resources
        ee()->cp->add_js_script('plugin', 'ee_url_title');
        ee()->cp->add_js_script('ui', 'sortable');
        ee()->cp->add_js_script('file', 'cp/sort_helper');
        ee()->cp->add_js_script(['file' => ['cp/confirm_remove']]);

        // Title
        $vars['cp_page_title'] = ($blockGroup->getName() == '') ? 'New Group' : $blockGroup->getName();

        $this->generateSidebar();

        // Build out the fields...
        $vars['sections'] = $sections;
        $vars['base_url'] = $this->pkg_url;
        $vars['blockGroup'] = $blockGroup;
        $vars['blockDefinitions'] = $blockGroupId ? $this->adapter->getBlockDefinitionsByGroupId($blockGroupId) : [];
        $vars['hiddenValues'] = ['groupId' => is_null($blockGroupId) ? 'new' : $blockGroupId];
        $vars['post_url'] = $this->makeCpUrl('block-group', ['groupId' => $blockGroupId]);
        $vars['save_button_text'] = 'save';
        $vars['save_button_text_working'] = 'btn_saving';
        $vars['eeVersion'] = 'ee'.App::majorVersion();
        $vars['eeVersionNumber'] = App::majorVersion();

        return $this->renderView('block-group', $vars, [
            $this->makeCpUrl('block-groups') => lang('bloqs_block_groups_title'),
            $this->makeCpUrl('block-group', ['groupId' => $blockGroupId]) => $vars['cp_page_title'],
        ]);
    }

    /**
     * @return array
     */
    public function blockDefinition()
    {
        // Load native fields language files
        ee()->lang->loadfile('fieldtypes');
        ee()->lang->loadfile('admin_content');
        ee()->lang->loadfile('channel');
        ee()->load->library('form_validation');

        ee()->cp->add_to_foot('<script type="text/javascript" src="'. URL_THIRD_THEMES .'bloqs/javascript/icon-selector.js?version='.BLOQS_VERSION.'"></script>');
        ee()->cp->add_to_head('<link rel="stylesheet" href="'. URL_THIRD_THEMES .'bloqs/css/icon-selector.css?version='.BLOQS_VERSION.'">');
        ee()->cp->add_to_foot('<script type="text/javascript" src="'. URL_THIRD_THEMES .'bloqs/javascript/edit-block-definition.js?version='.BLOQS_VERSION.'"></script>');
        ee()->cp->add_to_head('<link rel="stylesheet" href="'. URL_THIRD_THEMES .'bloqs/css/edit-block-definition.css?version='.BLOQS_VERSION.'">');

        $blockDefinitionId = ee()->input->get_post('definitionId');

        if ($blockDefinitionId == 'new') {
            $blockDefinitionId = null;
            $blockDefinition = new BlockDefinition();
            $blockDefinition
                ->setId(null)
                ->setName('')
                ->setShortName('')
            ;
        } else {
            $blockDefinitionId = intval($blockDefinitionId);
            $blockDefinition = $this->adapter->getBlockDefinitionById($blockDefinitionId);
        }

        $previewIconField = '';
        $blockGroupField = '';

        // Groups and Icon field are only supported in EE6+
        if (App::isGteEE6()) {
            $previewIconField = [
                'title' => 'bloqs_blockdefinition_preview_icon',
                'desc' => lang('bloqs_blockdefinition_preview_icon_info'),
                'fields' => [
                    'blockdefinition_preview_icon' => [
                        'required' => false,
                        'type' => 'html',
                        'content' => $this->makeViewFile('icon-selector')->render([
                            'name' => 'blockdefinition_preview_icon',
                            'icons' => $this->getIconOptions(),
                            'value' => $blockDefinition->getPreviewIcon(),
                        ]),
                    ]
                ]
            ];

            $blockGroupField = [
                'title' => 'bloqs_blockdefinition_group',
                'desc' => lang('bloqs_blockdefinition_group_info'),
                'fields' => [
                    'blockdefinition_group' => [
                        'required' => false,
                        'type' => 'dropdown',
                        'choices' => $this->adapter->getBlockGroupsDictionary(),
                        'value' => $blockDefinition->getGroupId(),
                    ]
                ]
            ];
        }

        $sections = [
            [
                [
                    'title' => 'bloqs_blockdefinition_name',
                    'desc' => lang('bloqs_blockdefinition_name_info'),
                    'fields' => [
                        'blockdefinition_name' => [
                            'required' => true,
                            'type' => 'text',
                            'value' => $blockDefinition->getName(),
                        ]
                    ]
                ],
                [
                    'title' => 'bloqs_blockdefinition_shortname',
                    'desc' => lang('bloqs_blockdefinition_shortname_info'),
                    'fields' => [
                        'blockdefinition_shortname' => [
                            'required' => true,
                            'type' => 'text',
                            'value' => $blockDefinition->getShortName(),
                        ]
                    ]
                ],
                [
                    'title' => 'bloqs_blockdefinition_instructions',
                    'desc' => lang('bloqs_blockdefinition_instructions_info'),
                    'fields' => [
                        'blockdefinition_instructions' => [
                            'required' => false,
                            'type' => 'textarea',
                            'value' => $blockDefinition->getInstructions(),
                        ]
                    ]
                ],
                [
                    'title' => 'bloqs_blockdefinition_deprecated',
                    'desc' => lang('bloqs_blockdefinition_deprecated_info'),
                    'attrs' => [
                        'class' => 'blocksft-setting-is-deprecated',
                    ],
                    'fields' => [
                        'blockdefinition_deprecated' => [
                            'required' => false,
                            'type' => 'yes_no',
                            'value' => $blockDefinition->isDeprecated(),
                        ]
                    ]
                ],
                [
                    'title' => 'bloqs_blockdefinition_deprecated_note',
                    'desc' => lang('bloqs_blockdefinition_deprecated_note_info'),
                    'attrs' => [
                        'class' => $blockDefinition->isDeprecated() ? 'blocksft-setting-deprecated-note' : 'blocksft-setting-deprecated-note hidden',
                    ],
                    'fields' => [
                        'blockdefinition_deprecated_note' => [
                            'required' => false,
                            'type' => 'textarea',
                            'value' => $blockDefinition->getDeprecatedNote(),
                        ]
                    ]
                ],
                $blockGroupField,
                [
                    'title' => 'bloqs_blockdefinition_preview_image',
                    'desc' => lang('bloqs_blockdefinition_preview_image_info'),
                    'fields' => [
                        'blockdefinition_preview_image' => [
                            'required' => false,
                            'type' => 'html',
                            'content' => (new FileField('blockdefinition_preview_image', $blockDefinition->getPreviewImage(), []))->render(),
                        ]
                    ]
                ],
                $previewIconField,
            ],
            lang('bloqs_blockdefinition_nestable_section') => $this->getNestingRulesFields($blockDefinition),
        ];

        $errors = [];

        if (!empty($_POST)) {
            $errors = $this->saveBlockDefinition($blockDefinition);
        }

        $atomDefinitionsView = $this->getAtomDefinitionsView($blockDefinition, $errors);

        $componentSections = [
            [
                [
                    'title' => 'bloqs_blockdefinition_component_is_component',
                    'desc' => lang('bloqs_blockdefinition_component_is_component_info'),
                    'attrs' => [
                        'class' => 'blocksft-setting-is-component',
                    ],
                    'fields' => [
                        'blockdefinition_is_component' => [
                            'required' => false,
                            'type' => 'yes_no',
                            'value' => $blockDefinition->isComponent(),
                        ],
                    ]
                ],
                [
                    'title' => 'bloqs_blockdefinition_component_is_editable',
                    'desc' => lang('bloqs_blockdefinition_component_is_editable_info'),
                    'attrs' => [
                        'class' => $blockDefinition->isComponent() ? '' : 'blocksft-setting-no-component-options',
                    ],
                    'fields' => [
                        'blockdefinition_is_editable' => [
                            'required' => false,
                            'type' => 'yes_no',
                            'value' => $blockDefinition->isEditable(),
                        ],
                    ]
                ]
            ]
        ];

        $componentBuilderView = $this->getComponentBuilderView($blockDefinition);

        //Page Specific Resources
        ee()->cp->add_js_script('plugin', 'ee_url_title');
        ee()->cp->add_js_script('ui', 'sortable');
        ee()->cp->add_js_script('file', 'cp/sort_helper');
        ee()->cp->add_js_script('file', 'cp/grid');
        ee()->cp->add_js_script(['file' => ['cp/confirm_remove']]);

        $vars['cp_page_title'] = ($blockDefinition->getName() == '') ? 'New Block' : $blockDefinition->getName();

        $this->generateSidebar();
        if (!$blockDefinition->getShortName()) {
            $this->addShortNameAutoComplete('blockdefinition_name', 'blockdefinition_shortname');
        }

        // Build out the fields...
        $vars['sections'] = $sections;
        $vars['componentSections'] = $componentSections;
        $vars['base_url'] = $this->pkg_url;
        $vars['blockDefinition'] = $blockDefinition;
        $vars['hiddenValues'] = ['definitionId' => is_null($blockDefinitionId) ? 'new' : $blockDefinitionId];
        $vars['atomDefinitionsView'] = $atomDefinitionsView;
        $vars['componentBuilderView'] = $componentBuilderView;
        $vars['post_url'] = $this->makeCpUrl('block-definition', ['definitionId' => $blockDefinitionId]);
        $vars['save_button_text'] = 'save';
        $vars['save_button_text_working'] = 'btn_saving';
        $vars['eeVersion'] = 'ee'.App::majorVersion();
        $vars['eeVersionNumber'] = App::majorVersion();

        ee()->javascript->output('EE.grid_settings();');

        return $this->renderView($this->viewFolder.'block-definition', $vars, [
            $this->makeCpUrl('block-definition', ['definitionId' => $blockDefinitionId]) => $vars['cp_page_title'],
        ]);
    }

    /**
     * @param BlockDefinition   $definition
     * @return array[]
     */
    private function getNestingRulesFields(BlockDefinition $definition)
    {
        $alert = ee('CP/Alert');
        $tip = $alert
            ->makeInline('shared-form')
            ->asWarning()
            ->withTitle(lang('bloqs_blockdefinition_nesting_description_title'))
            ->addToBody(lang('bloqs_blockdefinition_nesting_description'))
            ->cannotClose()
            ->render();

        $rootOnly = ($definition->getNestingRule('root') ?: 'any');
        $canHaveChildren = ($definition->getNestingRule('no_children') ?: 'n');
        $exactChildren = ($definition->getNestingRule('exact_children') ?: 0);
        $minChildren = ($definition->getNestingRule('min_children') ?: 0);
        $maxChildren = ($definition->getNestingRule('max_children') ?: 0);
        $showChildrenOptions = true;
        // If its a component then it's a hard no on any child options
        $isComponentDefinition = $definition->isComponent();

        if ($canHaveChildren === 'n' || $isComponentDefinition) {
            $exactChildren = 0;
            $minChildren = 0;
            $maxChildren = 0;
            $showChildrenOptions = false;
        }

        $currentBlocksCollection = [];
        $currentBlocks = $this->adapter->getBlockDefinitions();
        /** @var BlockDefinition $block */
        foreach ($currentBlocks as $block) {
            $currentBlocksCollection[$block->getId()] = $block->getName();
        }

        return [
            [
                'title' => $tip,
                'fields' => []
            ],
            [
                'title' => 'bloqs_blockdefinition_nesting_root',
                'desc' => lang('bloqs_blockdefinition_nesting_root_info'),
                'fields' => [
                    'blockdefinition_nesting[root]' => [
                        'required' => false,
                        'type' => 'radio',
                        'value' => $definition->getNestingRule('root'),
                        'choices' => [
                            'any' => 'Can be nested at any level',
                            'root_only' => 'Can only be root',
                            'no_root' => 'Can\'t be root and must be a child of another bloq',
                            'root_or_child_of' => 'Can be root, or child of another bloq',
                        ],
                    ]
                ]
            ],
            [
                'title' => 'bloqs_blockdefinition_nesting_child_of',
                'desc' => lang('bloqs_blockdefinition_nesting_child_of_info'),
                'attrs' => [
                    'class' => $rootOnly === 'root_only' ? 'blocksft-setting-child-of-options hidden' : 'blocksft-setting-child-of-options',
                ],
                'fields' => [
                    'blockdefinition_nesting[child_of]' => [
                        'required' => false,
                        'type' => 'checkbox',
                        'value' => $definition->getNestingRule('child_of'),
                        'choices' => $currentBlocksCollection,
                    ]
                ]
            ],
            [
                'title' => 'bloqs_blockdefinition_nesting_no_children',
                'desc' => lang('bloqs_blockdefinition_nesting_no_children_info'),
                'attrs' => [
                    'class' => 'blocksft-setting-can-have-children',
                ],
                'fields' => [
                    'blockdefinition_nesting[no_children]' => [
                        'required' => false,
                        'type' => 'yes_no',
                        'value' => $canHaveChildren,
                    ],
                ]
            ],
            [
                'title' => 'bloqs_blockdefinition_nesting_exact_children',
                'desc' => lang('bloqs_blockdefinition_nesting_exact_children_info'),
                'attrs' => [
                    'class' => !$showChildrenOptions ? 'blocksft-setting-children-options blocksft-setting-children-options__exact blocksft-setting-no-children' : 'blocksft-setting-children-options__exact blocksft-setting-children-options',
                ],
                'fields' => [
                    'blockdefinition_nesting[exact_children]' => [
                        'required' => false,
                        'type' => 'slider',
                        'unit' => '',
                        'min' => 0,
                        'max' => 25,
                        'value' => $exactChildren,
                    ],
                ]
            ],
            [
                'title' => 'bloqs_blockdefinition_nesting_min_children',
                'desc' => lang('bloqs_blockdefinition_nesting_min_children_info'),
                'attrs' => [
                    'class' => !$showChildrenOptions ? 'blocksft-setting-children-options blocksft-setting-children-options__min blocksft-setting-no-children' : 'blocksft-setting-children-options__min blocksft-setting-children-options' . ($exactChildren > 0 ? ' blocksft-setting-no-children' : ''),
                ],
                'fields' => [
                    'blockdefinition_nesting[min_children]' => [
                        'required' => false,
                        'type' => 'slider',
                        'unit' => '',
                        'min' => 0,
                        'max' => 25,
                        'value' => $minChildren,
                    ],
                ]
            ],
            [
                'title' => 'bloqs_blockdefinition_nesting_max_children',
                'desc' => lang('bloqs_blockdefinition_nesting_max_children_info'),
                'attrs' => [
                    'class' => !$showChildrenOptions ? 'blocksft-setting-children-options blocksft-setting-children-options__max blocksft-setting-no-children' : 'blocksft-setting-children-options__max blocksft-setting-children-options' . ($exactChildren > 0 ? ' blocksft-setting-no-children' : ''),
                ],
                'fields' => [
                    'blockdefinition_nesting[max_children]' => [
                        'required' => false,
                        'type' => 'slider',
                        'unit' => '',
                        'min' => 0,
                        'max' => 25,
                        'value' => $maxChildren,
                    ],
                ]
            ],
        ];
    }

    /**
     * @param BlockDefinition $blockDefinition
     * @return array
     */
    private function saveBlockDefinition(BlockDefinition $blockDefinition)
    {
        ee()->form_validation->setCallbackObject($blockDefinition);
        ee()->form_validation->set_rules('blockdefinition_name', 'Name', 'trim|required');
        ee()->form_validation->set_rules('blockdefinition_shortname', 'Short Name', 'trim|required|callback_hasUniqueShortname[' . $blockDefinition->getId() . ']');

        $errors = [];
        $isValid = ee()->form_validation->run();

        if ($isValid === false) {
            $this->addAlert(false, 'blocks_settings_alert', lang('bloqs_blockdefinition_alert_title'), lang('bloqs_blockdefinition_alert_message'));
        } else {
            $groupId = ee()->input->post('blockdefinition_group');
            $name = ee()->input->post('blockdefinition_name');
            $shortName = ee()->input->post('blockdefinition_shortname');
            $instructions = ee()->input->post('blockdefinition_instructions');
            $deprecated = ee()->input->post('blockdefinition_deprecated') === 'y' ? 1 : 0;
            $deprecatedNote = ee()->input->post('blockdefinition_deprecated_note') ?: '';
            $previewImage = ee()->input->post('blockdefinition_preview_image');
            $previewIcon = ee()->input->post('blockdefinition_preview_icon');
            $isComponent = ee()->input->post('blockdefinition_is_component') === 'y' ? 1 : 0;
            $isEditable = ee()->input->post('blockdefinition_is_editable') === 'y' ? 1 : 0;
            $settings['nesting'] = $this->getNestingSettings();

            $atomSettings = ee()->input->post('grid');
            $errors = array_merge($errors, $this->validateAtomSettings($atomSettings));

            if (empty($errors)) {
                $blockDefinition->setGroupId($groupId);
                $blockDefinition->setName($name);
                $blockDefinition->setShortName($shortName);
                $blockDefinition->setInstructions($instructions);
                $blockDefinition->setDeprecated($deprecated);
                $blockDefinition->setDeprecatedNote($deprecatedNote);
                $blockDefinition->setPreviewImage($previewImage);
                $blockDefinition->setPreviewIcon($previewIcon);
                $blockDefinition->setSettings($settings);
                $blockDefinition->setIsComponent($isComponent);
                $blockDefinition->setIsEditable($isEditable);


                if (!$blockDefinition->getId()) {
                    $this->adapter->createBlockDefinition($blockDefinition);
                    $this->addAlert(true, 'blocks_settings_alert', lang('bloqs_blockdefinition_success_title'), lang('bloqs_blockdefinition_success_title_info'));
                } else {
                    $this->adapter->updateBlockDefinition($blockDefinition);
                    $this->addAlert(true, 'blocks_settings_alert', lang('bloqs_blockdefinition_success_title'));
                }

                ee()->functions->clear_caching('jumpmenu');

                $this->applyAtomSettings($blockDefinition, $atomSettings, $this->adapter);
                $this->adapter->saveBlockComponent($blockDefinition, ee()->input->post('field_id_0') ?: []);

                ee()->functions->redirect($this->makeCpUrl('block-definition', ['definitionId' => $blockDefinition->getId()]));
            }
        }

        return $errors;
    }

    /**
     * @return array
     */
    private function getIconOptions()
    {
        $icons = require_once 'views/icons.php';
        $collection = [];

        foreach ($icons as $name => $type) {
            $collection[$name .' '. $type] = $name;
        }

        return $collection;
    }

    /**
     * @return array
     */
    private function getNestingSettings()
    {
        $post = ee()->input->post('blockdefinition_nesting');

        // Cleanup empty values that the multi-select field in EE likes to add :/
        $post['child_of'] = isset($post['child_of']) && is_array($post['child_of']) ? array_filter($post['child_of']) : null;

        if ($post['root'] === 'root_only') {
            $post['child_of'] = null;
        }

        // Zero out the values if children are disabled. JS will hide the fields, make sure the DB is updated accordingly.
        if ($post['no_children'] === 'n') {
            $post['exact_children'] = 0;
            $post['min_children'] = 0;
            $post['max_children'] = 0;
        }

        if ($post['exact_children'] > 0) {
            $post['min_children'] = 0;
            $post['max_children'] = 0;
        }

        return $post;
    }

    /**
     * @param BlockDefinition $blockDefinition
     * @return string
     * @throws Exception
     */
    private function getComponentBuilderView(BlockDefinition $blockDefinition)
    {
        // Can't nest components, so remove them from the options list when rendering the field
        $definitions = array_filter($this->adapter->getBlockDefinitions(), function (BlockDefinition $definition) {
            return !$definition->isComponent();
        });

        // Remove what should be the first block, which is the definition we're building. We only want child blocks
        $definitionBlocks = array_filter($blockDefinition->getBlocks(), function (Block $block) use ($blockDefinition) {
            return $block->getDefinition()->getId() !== $blockDefinition->getId();
        });

        $field = (new StandaloneField('component_builder', 0, [
            'nestable' => 'y',
            'menu_grid_display' => 'n',
            'component_builder' => 'y',
        ]))
            ->setData('')
            ->setBlocks($definitionBlocks)
            ->setBlockDefinitions($definitions);

        return $field->render();
    }

    /**
     * Generate the atomdefinitions view
     *
     * @param BlockDefinition $blockDefinition
     * @param array $atom_errors
     * @return string
     */
    private function getAtomDefinitionsView(BlockDefinition $blockDefinition, $atom_errors = [])
    {
        $vars = [];
        $vars['columns'] = [];

        foreach ($blockDefinition->getAtomDefinitions() as $atomDefinition) {
            $field_errors = (!empty($atom_errors['col_id_' . $atomDefinition->id])) ? $atom_errors['col_id_' . $atomDefinition->id] : [];
            $atomDefinitionView = $this->getAtomDefinitionView($atomDefinition, null, $field_errors);
            $vars['columns'][] = $atomDefinitionView;
        }

        // Fresh settings forms ready to be used for added columns
        $vars['settings_forms'] = [];

        /** @var FieldType  $fieldType */
        foreach ($this->_ftManager->getFieldTypes() as $fieldType) {
            $fieldName = $fieldType->getType();
            $vars['settings_forms'][$fieldName] = $this->getAtomDefinitionSettingsForm(null, $fieldName);
        }

        // Will be our component for newly-created columns
        $vars['blank_col'] = $this->getAtomDefinitionView(null);
        $vars['eeVersion'] = 'ee'.App::majorVersion();
        $vars['eeVersionNumber'] = App::majorVersion();

        if (empty($vars['columns'])) {
            $vars['columns'][] = $vars['blank_col'];
        }

        $view = $this->renderView($this->viewFolder.'atom-definitions', $vars);

        return $view['body'];
    }

    /**
     * create the single view for each atom 'block'
     *
     * @param AtomDefinition $atomDefinition
     * @param $column
     * @param $field_errors
     *
     * @return string  Rendered column view for settings page
     */
    private function getAtomDefinitionView($atomDefinition, $column = NULL, $field_errors = array())
    {
        $fieldtypes = $this->_ftManager->getFieldTypes();

        // Create a dropdown-friendly array of available fieldtypes
        $fieldtypesLookup = array();
        /** @var FieldType $fieldType */
        foreach ($fieldtypes as $fieldType) {
            $fieldtypesLookup[$fieldType->getType()] = $fieldType->getName();
        }

        $field_name = (is_null($atomDefinition)) ? 'new_0' : 'col_id_' . $atomDefinition->getId();

        $settingsForm = (is_null($atomDefinition))
            ? $this->getAtomDefinitionSettingsForm(null, 'text')
            : $this->getAtomDefinitionSettingsForm($atomDefinition, $atomDefinition->getType());

        $view = $this->renderView($this->viewFolder.'atom-definition', [
            'atomDefinition' => $atomDefinition,
            'field_name' => $field_name,
            'settingsForm' => $settingsForm,
            'fieldtypes' => $fieldtypesLookup,
            'field_errors' => $field_errors,
        ]);

        return $view['body'];
    }

    /**
     * Returns rendered HTML for the custom settings form of a grid column type
     *
     * @param AtomDefinition $atomDefinition
     * @param string $type
     * @return string Rendered HTML settings form for given fieldtype and column data
     */
    private function getAtomDefinitionSettingsForm($atomDefinition, $type)
    {
        /** @var Api_channel_fields $ft_api */
        $ft_api = ee()->api_channel_fields;
        $settings = null;

        // Returns blank settings form for a specific fieldtype
        if (is_null($atomDefinition)) {
            $ft = $ft_api->setup_handler($type, true);
            $ft->_init(['content_type' => 'grid']);

            if ($ft_api->check_method_exists('grid_display_settings')) {
                if ($ft->accepts_content_type('blocks/1')) {
                    $ft->_init(['content_type' => 'blocks/1']);
                } elseif ($ft->accepts_content_type('bloqs/1')) {
                    $ft->_init(['content_type' => 'bloqs/1']);
                } elseif ($ft->accepts_content_type('grid')) {
                    $ft->_init(['content_type' => 'grid']);
                }
                $settings = $ft_api->apply('grid_display_settings', [[]]);

            } elseif ($ft_api->check_method_exists('display_settings')) {
                if ($ft->accepts_content_type('grid')) {
                    $ft->_init(['content_type' => 'grid']);
                }
                $settings = $ft_api->apply('display_settings', [[]]);
            }

            return $this->_view_for_col_settings($atomDefinition, $type, $settings);
        }

        $fieldtype = $this->_ftManager->instantiateFieldtype(
            $atomDefinition,
            null,
            null,
            0, // Field ID? At this point, we don't have one.
            0
        );

        if (is_null($fieldtype)) {
            ee()->lang->loadfile('bloqs');

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

        $atomSettings = $atomDefinition->getSettings();

        // https://boldminded.com/support/ticket/2923
        // https://boldminded.com/support/ticket/3071
        // Make sure the radio button for Value/Label Pairs is selected by default.
        // Grid behaves the same way, where you can add value/label pairs without
        // selecting the radio and when saving it defaults to "populate from another channel field"
        if (
            in_array($type, ['multi_select', 'select']) &&
            !isset($atomSettings['field_pre_populate']) &&
            isset($atomSettings['value_label_pairs'])
        ) {
            $atomSettings['field_pre_populate'] = 'v';
        }

        $settings = $fieldtype->displaySettings($atomSettings);

        // Otherwise, return the pre-populated settings form based on column settings
        return $this->_view_for_col_settings($atomDefinition, $type, $settings);
    }

    /**
     * Allow a user to copy a block definition and its atoms
     */
    public function copyBlock()
    {
        $blockDefinitionId = ee()->input->get_post('definitionId');
        $blockDefinitionName = ee()->input->get_post('blockdefinition_name');
        $blockDefinitionShortName = ee()->input->get_post('blockdefinition_shortname');
        $blockDefinitionId = intval($blockDefinitionId);
        $blockDefinition = $this->adapter->getBlockDefinitionById($blockDefinitionId);

        if (!empty($_POST) &&
            !is_null($blockDefinition) &&
            $blockDefinitionName &&
            $blockDefinitionShortName
        ) {
            ee()->functions->clear_caching('all');
            $this->adapter->copyBlockDefinition($blockDefinitionId, $blockDefinitionName, $blockDefinitionShortName);
            ee()->functions->redirect($this->pkg_url, false, 302);
        }
    }

    /**
     * @return void
     * @throws Exception
     */
    public function confirmDelete()
    {
        $blockDefinitionId = ee()->input->post('definitionId');
        $blockDefinitionId = intval($blockDefinitionId);
        $blockDefinition = $this->adapter->getBlockDefinitionById($blockDefinitionId);

        if (!empty($_POST) && !is_null($blockDefinition)) {
            ee()->functions->clear_caching('all');
            $this->adapter->deleteBlockDefinition($blockDefinitionId);
            $this->addAlert(true, 'blocks_settings_alert', lang('bloqs_blockdefinition_alert_delete_title'));
            ee()->functions->redirect($this->pkg_url, false, 302);
        }
    }

    /**
     * @return void
     * @throws Exception
     */
    public function confirmDeleteGroup()
    {
        $groupId = ee()->input->post('groupId');
        $groupId = intval($groupId);
        $blockGroup = $this->adapter->getBlockGroupById($groupId);

        if (!empty($_POST) && !is_null($blockGroup)) {
            $this->adapter->deleteBlockGroup($groupId);
            $this->addAlert(true, 'blocks_settings_alert', lang('bloqs_blockgroup_alert_delete_title'));
            ee()->functions->redirect($this->makeCpUrl('block-groups'), false, 302);
        }
    }

    /**
     * Generates the mcp view for the controller action
     *
     * @param $name - string - name of view file
     * @param $vars - array
     *
     * @return array
     */
    private function renderView(string $name, array $vars = [], array $breadcrumbs = [])
    {
        return [
            'breadcrumb' => $breadcrumbs,
            'body' => $this->makeViewFile($name)->render($vars),
        ];
    }

    /**
     * @param $fileName
     * @return \ExpressionEngine\Service\View\View
     */
    private function makeViewFile($fileName)
    {
        return ee('View')->make($this->pkg . ':' . $fileName);
    }

    /**
     * Returns rendered HTML for the custom settings form of a grid column type,
     * helper method for Grid_lib::get_settings_form
     *
     * @param AtomDefinition $atomDefinition
     * @param string $type
     * @param array $settings
     * @return string Rendered HTML settings form for given fieldtype and column data
     */
    protected function _view_for_col_settings($atomDefinition, $type, $settings)
    {
        $settings_view = $this->renderView(
            $this->viewFolder.'atom-definition-settings',
            array(
                'atomDefinition' => $atomDefinition,
                'col_type' => $type,
                'col_settings' => (empty($settings)) ? array() : $settings
            )
        );

        $col_id = (is_null($atomDefinition)) ? 'new_0' : 'col_id_' . $atomDefinition->id;

        $body = $settings_view['body'];
        // Because several new fields are React fields with encoded settings which are used to render the
        // final html output instead of having access to the html here like all other basic fieldtypes.
        if (preg_match_all('/data-select-react="(.*?)" data-input-value="(.*?)"/', $body, $matches)) {
            $body = $this->updateReactComponents($col_id, $body, $matches);
        }

        if (preg_match_all('/data-dropdown-react="(.*?)" data-input-value="(.*?)"/', $body, $matches)) {
            $body = $this->updateReactComponents($col_id, $body, $matches);
        }

        // Namespace form field names
        return $this->_namespace_inputs(
            $body,
            '$1name="grid[cols][' . $col_id . '][col_settings][$2]$3"'
        );
    }

    private function updateReactComponents(string $col_id, string $body, array $matches = []): string
    {
        foreach ($matches[1] as $index => $settingsMatch) {
            $settings = json_decode(base64_decode($settingsMatch));
            $optionName = $matches[2][$index];
            $newOptionName = 'grid[cols][' . $col_id . '][col_settings][' . $optionName . ']';

            $settings->name = $newOptionName;
            $settings = base64_encode(json_encode($settings));

            $body = str_replace($settingsMatch, $settings, $body);
            $body = preg_replace('/data-input-value="'. $optionName .'"/', 'data-input-value="' . $newOptionName . '"', $body);
        }

        return $body;
    }

    /**
     * Performs find and replace for input names in order to namespace them
     * for a POST array
     *
     * @param string  String to search
     * @param string  String to use for replacement
     * @return string  String with namespaced inputs
     */
    protected function _namespace_inputs($search, $replace)
    {
        return preg_replace(
            '/(<[input|select|textarea][^>]*)name=["\']([^"\'\[\]]+)([^"\']*)["\']/',
            $replace,
            $search
        );
    }

    /**
     * @param $validate
     * @return array
     */
    protected function prepareErrors($validate)
    {
        $errors = array();
        $field_names = array();

        // Gather error messages and fields with errors so that we can
        // display the error messages and highlight the fields that have errors
        foreach ($validate as $column => $fields) {
            foreach ($fields as $field => $error) {
                $errors[] = $error;
                $field_names[] = 'grid[cols][' . $column . '][' . $field . ']';
            }
        }

        // Make error messages unique and convert to a string to pass to form validaiton library
        $errors = array_unique($errors);
        $error_string = '';
        foreach ($errors as $error) {
            $error_string .= lang($error) . '<br>';
        }

        return array(
            'field_names' => $field_names,
            'error_string' => $error_string
        );
    }

    /**
     * @param $settings
     * @return array
     */
    private function validateAtomSettings($settings)
    {
        $errors = array();
        $col_names = array();

        // Create an array of column names for counting to see if there are
        // duplicate column names; they should be unique
        foreach ($settings['cols'] as $col_field => $column) {
            $col_names[] = $column['col_name'];
        }

        $col_name_count = array_count_values($col_names);

        foreach ($settings['cols'] as $col_field => $column) {
            // Atom labels are required
            if (empty($column['col_label'])) {
                $errors[$col_field]['col_label'] = 'grid_col_label_required';
            }

            // Atom names are required
            if (empty($column['col_name'])) {
                $errors[$col_field]['col_name'] = 'grid_col_name_required';
            }

            // Atom names are unique, and don't match the bloq name.
            if (!empty($column['col_name']) && $column['col_name'] === ee()->input->post('blockdefinition_shortname')) {
                $errors[$col_field]['col_name'] = lang('bloqs_invalid_atom_name');
            }


            // @todo - why is this disabled? Before my time...
            // Columns cannot be the same name as our protected modifiers
            /*
            elseif (in_array($column['col_name'], ee()->grid_parser->reserved_names))
            {
              $errors[$col_field]['col_name'] = 'grid_col_name_reserved';
            }
            */
            // There cannot be duplicate column names
            elseif ($col_name_count[$column['col_name']] > 1) {
                $errors[$col_field]['col_name'] = 'grid_duplicate_col_name';
            }

            // Column names must contain only alpha-numeric characters and no spaces
            if (preg_match('/[^a-z0-9\-\_]/i', $column['col_name'])) {
                $errors[$col_field]['col_name'] = 'grid_invalid_column_name';
            }

            $column['col_id'] = (strpos($col_field, 'new_') === FALSE)
                ? str_replace('col_id_', '', $col_field) : FALSE;
            $column['col_required'] = isset($column['col_required']) ? 'y' : 'n';
            $column['col_settings']['col_required'] = $column['col_required'];

            $atomDefinition = new AtomDefinition();
            $atomDefinition
                ->setId(intval($column['col_id']))
                ->setShortName($column['col_name'])
                ->setName($column['col_label'])
                ->setInstructions($column['col_instructions'])
                ->setOrder(1)
                ->setType($column['col_type'])
                ->setSettings($column['col_settings'])
            ;

            $fieldtype = $this->_ftManager->instantiateFieldtype($atomDefinition, null, null, 0, 0);

            // Let fieldtypes validate their Grid column settings; we'll
            // specifically call grid_validate_settings() because validate_settings
            // works differently and we don't want to call that on accident
            $ft_validate = $fieldtype->validateSettings($column['col_settings']);

            if (is_string($ft_validate)) {
                $errors[$col_field]['custom'] = $ft_validate;
            }
        }

        if (!empty($errors)) {
            $alertErrors = [];

            foreach ($errors as $error) {
                $alertErrors = array_merge($alertErrors, array_values($error));
            }

            $this->addAlert(
                false,
                'blocks_settings_alert',
                lang('bloqs_blockdefinition_atomdefinition_alert_title'),
                $alertErrors
            );
        }

        return $errors;
    }

    /**
     * @param $blockDefinition
     * @param $settings
     * @param Adapter $adapter
     */
    private function applyAtomSettings(BlockDefinition $blockDefinition, $settings, Adapter $adapter)
    {
        //$new_field = ee()->grid_model->create_field($settings['field_id'], $this->content_type);

        // Keep track of column IDs that exist so we can compare it against
        // other columns in the DB to see which we should delete
        $col_ids = [];

        // Determine the order of each atom definition.
        $order = 0;

        // Go through ALL posted columns for this field
        foreach ($settings['cols'] as $col_field => $column) {
            $order++;
            // Attempt to get the column ID; if the field name contains 'new_',
            // it's a new field, otherwise extract column ID
            $column['col_id'] = (strpos($col_field, 'new_') === FALSE)
                ? str_replace('col_id_', '', $col_field) : FALSE;

            $id = $column['col_id'] ? intval($column['col_id']) : null;

            $column['col_required'] = (isset($column['col_required']) && $column['col_required'] == 'y') ? 'y' : 'n';
            $column['col_settings']['col_required'] = $column['col_required'];

            // When creating a new block in EE4 that only has a text field, and the user has not clicked on the
            // field type option (a React field) the following 3 fields do not save default values even though
            // they have the .act class and have checked="checked" in the html.
            // https://boldminded.com/support/ticket/1637
            if ($column['col_type'] === 'text') {
                if (!isset($column['col_settings']['field_text_direction'])) {
                    $column['col_settings']['field_text_direction'] = 'ltr';
                }
                if (!isset($column['col_settings']['field_fmt'])) {
                    $column['col_settings']['field_fmt'] = 'none';
                }
                if (!isset($column['col_settings']['field_content_type'])) {
                    $column['col_settings']['field_content_type'] = 'all';
                }
            }

            // We could find the correct atom definition in the block definition, but we'd end up overwriting all of
            // it's properties anyway, so we may as well make a new model object that represents the same atom definition.
            $atomDefinition = new AtomDefinition();
            $atomDefinition
                ->setId($id)
                ->setShortName($column['col_name'])
                ->setName($column['col_label'])
                ->setInstructions($column['col_instructions'])
                ->setOrder($order)
                ->setType($column['col_type'])
                ->setSettings($column['col_settings'])
            ;

            $atomDefinition->settings = $this->_save_settings($atomDefinition);
            $atomDefinition->settings['col_required'] = $column['col_required'];
            $atomDefinition->settings['col_search'] = isset($column['col_search']) ? $column['col_search'] : 'n';

            if (is_null($atomDefinition->id)) {
                $this->adapter->createAtomDefinition($blockDefinition->id, $atomDefinition);
            } else {
                $this->adapter->updateAtomDefinition($atomDefinition);
            }

            $col_ids[] = $atomDefinition->id;
        }

        // Delete existing atoms that were not included.
        foreach ($blockDefinition->getAtomDefinitions() as $atomDefinition) {
            if (!in_array($atomDefinition->id, $col_ids)) {
                $this->adapter->deleteAtomDefinition($atomDefinition->id);
            }
        }
    }

    /**
     * @param $atomDefinition
     * @return array|mixed|null
     */
    protected function _save_settings($atomDefinition)
    {
        if (!isset($atomDefinition->settings)) {
            $atomDefinition->settings = [];
        }

        $fieldtype = $this->_ftManager->instantiateFieldtype($atomDefinition);

        if (!($settings = $fieldtype->saveSettings($atomDefinition->settings))) {
            return $atomDefinition->settings;
        }

        return $settings;
    }

    /**
     * @param bool $type false = issue/error || true = success
     * @param string $name
     * @param string $title
     * @param string|array $msg
     */
    private function addAlert(bool $shouldDefer, string $name, string $title, $msg = '')
    {
        /** @var \ExpressionEngine\Service\Alert\Alert $alert */
        $alert = ee('CP/Alert')->makeInline($name);
        $alert->withTitle($title);

        if ($msg) {
            $alert->addToBody($msg);
        }

        if ($shouldDefer === true) {
            $alert
                ->asSuccess()
                ->defer();
        } else {
            $alert
                ->asIssue()
                ->now();
        }
    }

    /**
     * @param string $fieldName
     */
    private function addShortNameAutoComplete(string $name, string $shortName)
    {
        ee()->cp->add_js_script('plugin', 'ee_url_title');
        ee()->javascript->output('
            $("input[name=' . $name . ']").bind("keyup keydown", function() {
              $(this).ee_url_title("input[name=' . $shortName . ']", true);
            });
        ');
    }

    /**
     * @return string
     * @throws Exception
     */
    public function fetch_template_code()
    {
        // Ugh :(
        require_once SYSPATH.'ee/legacy/fieldtypes/EE_Fieldtype.php';

        ee()->load->library('addons');
        $template = new TemplateCodeRenderer($this->adapter, ee()->addons->get_installed('fieldtypes'));

        $fieldName = ee()->input->get('field_name');
        $includeBlocks = ee()->input->get('include_blocks') ?: [];
        $nestable = ee()->input->get('nestable') === 'true';

        if (!$fieldName) {
            return '';
        }

        $str = $template->renderFieldTemplate($fieldName, $includeBlocks, $nestable);

        echo $str . PHP_EOL; die;
    }

    public function releaseNotes()
    {
        $this->generateSidebar();

        $version = new Version();
        $allVersions = $version->setAddon('bloqs')->fetchAll();

        $releases = [];

        foreach ($allVersions as $version) {
            $releases[] = [
                'date' => $version->dateFormatted,
                'version' => $version->version,
                'notes' => html_entity_decode($version->notes),
                'isNew' => version_compare($version->version, BLOQS_VERSION, '>'),
                'currentVersion' => BLOQS_VERSION,
            ];
        }

        $vars['releases'] = $releases;

        $vars['message'] = ee('CP/Alert')->makeInline('bloqs-releases')
            ->asAttention()
            ->cannotClose()
            ->withTitle('Stay up-to-date!')
            ->addToBody('The latest version of Bloqs can be downloaded from your <a href="https://boldminded.com/account/licenses">BoldMinded account</a> or <a href="https://expressionengine.com/store/licenses-add-ons">ExpressionEngine.com</a>')
            ->render();

        return $this->renderView('release-notes', $vars, [
            $this->makeCpUrl('release-notes') => lang('bloqs_release_notes'),
        ]);
    }
}
