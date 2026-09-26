<?php

use BoldMinded\Bloqs\Controller\ModalController;
use ExpressionEngine\Library\CP\Table;

$table = ee('CP/Table', ['sortable' => false]);
$table->setNoResultsText('bloqs_groups_no_results');

$tbl_cols = [
    'bloqs_blockgroup_name',
    'bloqs_blockgroup_manage' => ['type' => Table::COL_TOOLBAR],
];
$table->setColumns($tbl_cols);

$rows = [];

$modalController = new ModalController();

/** @var \BoldMinded\Bloqs\Entity\BlockGroup $blockGroup */
foreach($blockGroups as $blockGroup) {
    $rows[] = [
        [
            'href' => ee('CP/URL')->make('addons/settings/bloqs/block-group', ['groupId' => $blockGroup->getId()])->compile(),
            'content' => $blockGroup->getName(),
        ],
        [
            'toolbar_items' => [
                'edit' => [
                    'href' => ee('CP/URL')->make('addons/settings/bloqs/block-group', ['groupId' => $blockGroup->getId()])->compile(),
                    'title' => lang('edit'),
                ],
                'remove' => [
                    'href'    => '',
                    'title'   => lang('delete'),
                    'class'   => 'm-link',
                    'rel'     => 'modal-confirm-remove',
                    'data-confirm' => 'Group Name: '.$blockGroup->getName(),
                    'data-group-id' => $blockGroup->getId(),
                ],
            ]
        ],
    ];

    $modalController->create('modal-confirm-remove', 'ee:_shared/modal_confirm_remove', [
        'form_url' => $confirmDeleteGroupUrl,
        'hidden' => ['groupId' => $blockGroup->getId()],
        'checklist' => [['kind' => 'Group Name', 'desc' => $blockGroup->getName()]]
    ]);

    $sections = [];

    $sections[0][] = [
        'title' => 'bloqs_group_name',
        'desc' => lang('bloqs_group_name_info'),
        'fields' => [
            'blockdefinition_name' => [
                'required' => 1,
                'type' => 'text',
                'value' => '',
            ]
        ]
    ];
    $sections[0][] = [
        'title' => 'bloqs_group_shortname',
        'desc' => lang('bloqs_group_shortname_info'),
        'fields' => [
            'blockdefinition_shortname' => [
                'required' => 1,
                'type' => 'text',
                'value' => '',
            ]
        ]
    ];
}

$table->setData($rows);

?>

<div class="bloqs">
    <div class="tbl-ctrls">
        <fieldset class="tbl-search right">
            <a class="btn tn action" href="<?php echo $blockGroupUrl; ?>"><?php echo lang('bloqs_group_add'); ?></a>
        </fieldset>
        <h1><?php echo  lang('bloqs_block_groups_title'); ?></h1>

        <div class="panel">
            <div class="panel-body">
                <?php echo sprintf(lang('bloqs_groups_overview'), 'https://docs.boldminded.com/bloqs/docs/features/bloq-groups'); ?>
            </div>
        </div>

        <?php echo ee('CP/Alert')->getAllInlines()?>
        <?php $this->embed('ee:_shared/table', $table->viewData()); ?>
    </div>
</div>

