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

<div class="form-standard blocks">
    <?=form_open($post_url, '', $hiddenValues )?>
    <div class="form-btns form-btns-top">
        <h1>
            <?=(isset($cp_page_title_alt)) ? $cp_page_title_alt : $cp_page_title?>
        </h1>
        <?=cp_form_submit($save_button_text, $save_button_text_working, NULL, (isset($errors) && $errors->isNotValid()))?>
    </div>

    <?=ee('CP/Alert')->get('blocks_settings_alert')?>
    <?php
    if( isset($sections) ) {
        foreach( $sections as $name => $settings ) {
            $this->embed('ee:_shared/form/section', array('name' => $name, 'settings' => $settings) );
        }
    }
    ?>

    <h2 data-section-group="blocks" style="display: block;"><?=lang('bloqs_blockdefinition_field_header')?></h2>
    <?=ee('CP/Alert')->get('blocks_block_alert')?>
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

    <fieldset class="form-btns">
        <?=cp_form_submit($save_button_text, $save_button_text_working, NULL, (isset($errors) && $errors->isNotValid()))?>
    </fieldset>
    <?=form_close()?>
</div>
