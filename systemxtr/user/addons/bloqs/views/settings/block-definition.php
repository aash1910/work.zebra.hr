<?php
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
?>

<div class="bloqs box panel">
    <div class="form-standard">
        <?php echo form_open($post_url, '', $hiddenValues); ?>
        <div class="panel-heading">
            <div class="form-btns form-btns-top">
                <div class="title-bar title-bar--large">
                    <h3 class="title-bar__title"><?php echo $cp_page_title_alt ?? $cp_page_title; ?></h3>
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
            <h2 data-section-group="blocks" style="display: block;">
                <?php echo lang('bloqs_blockdefinition_field_header')?>
            </h2>
            <?php echo ee('CP/Alert')->get('blocks_block_alert')?>
            <div class="block-container">
                <?php echo $atomDefinitionsView; ?>
            </div>

            <h2 data-section-group="component-builder" style="display: block;">
                <?php echo lang('bloqs_blockdefinition_component_builder')?>
            </h2>
            <fieldset>
                <div class="field-instruct">
                    <em><?php echo lang('bloqs_blockdefinition_component_builder_info')?></em>
                </div>
            </fieldset>
            <?php
            if( isset($componentSections) ) {
                foreach( $componentSections as $name => $settings ) {
                    $this->embed('ee:_shared/form/section', array('name' => $name, 'settings' => $settings));
                }
            }
            ?>
            <div class="block-container component-builder">
                <fieldset>
                    <div class="field-instruct">
                        <label><?php echo lang('bloqs_blockdefinition_component_builder_field')?></label>
                    </div>
                    <div class="field-control">
                        <?php echo lang('bloqs_blockdefinition_component_builder_field_info')?>
                    </div>
                </fieldset>
                <?php echo $componentBuilderView; ?>
            </div>

        </div>
        <div class="panel-footer">
            <div class="form-btns">
                <?php echo cp_form_submit($save_button_text, $save_button_text_working, null, (isset($errors) && $errors->isNotValid())); ?>
            </div>
        </div>
        <?php echo form_close()?>
    </div>
</div>
