<?php

use ExpressionEngine\Library\CP\Table;

// Show "Required Fields" in header if there are any required fields
if (!isset($required) || !is_bool($required)) {
    $required = FALSE;

    foreach ($sections as $name => $settings) {
        foreach ($settings as $setting) {
            if ( ! is_array($setting)) {
                continue;
            }

            foreach ($setting['fields'] as $field_name => $field) {
                if ($required = (isset($field['required']) && $field['required'] === true)) {
                    break 3;
                }
            }
        }
    }
}

$table = ee('CP/Table', ['sortable' => false]);
$table->setNoResultsText('bloqs_definitions_no_results');

$tbl_cols = [
    'bloqs_blockdefinitions_name',
    'bloqs_blockdefinitions_shortname',
    'bloqs_blockdefinitions_manage' => ['type' => Table::COL_TOOLBAR],
];
$table->setColumns($tbl_cols);

$rows = [];

/** @var \BoldMinded\Bloqs\Entity\BlockDefinition $blockDefinition */
foreach($blockDefinitions as $blockDefinition) {
    $rows[] = [
        [
            'href' => ee('CP/URL')->make('addons/settings/bloqs/block-definition', ['definitionId' => $blockDefinition->getId()])->compile(),
            'content' => $blockDefinition->getName(),
        ],
        [
            'content' => $blockDefinition->shortname,
        ],
        [
            'toolbar_items' => [
                'edit' => [
                    'href' => ee('CP/URL')->make('addons/settings/bloqs/block-definition', ['definitionId' => $blockDefinition->getId()])->compile(),
                    'title' => lang('edit'),
                ],
            ]
        ],
    ];
}

$table->setData($rows);

?>

<div class="bloqs box panel">
    <div class="form-standard">
        <?php echo form_open($post_url, '', $hiddenValues); ?>
        <div class="panel-heading">
            <div class="form-btns form-btns-top">
                <div class="title-bar title-bar--large">
                    <h3 class="title-bar__title"><?php echo (isset($cp_page_title_alt)) ? $cp_page_title_alt : $cp_page_title; ?></h3>
                    <div class="title-bar__extra-tools">
                        <?php echo cp_form_submit($save_button_text, $save_button_text_working, null, (isset($errors) && $errors->isNotValid())) ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="panel-body">
            <?php echo ee('CP/Alert')->get('blocks_settings_alert')?>
            <?php
            if( isset($sections) ) {
                foreach( $sections as $name => $settings ) {
                    $this->embed('ee:_shared/form/section', array('name' => $name, 'settings' => $settings));
                }
            }
            ?>

            <h2><?php echo lang('bloqs_blocks_in_group') ?></h2>
            <?php $this->embed('ee:_shared/table', $table->viewData()); ?>
        </div>
        <div class="panel-footer">
            <div class="form-btns">
                <?php echo cp_form_submit($save_button_text, $save_button_text_working, null, (isset($errors) && $errors->isNotValid())); ?>
            </div>
        </div>
        <?php echo form_close()?>
    </div>
</div>

