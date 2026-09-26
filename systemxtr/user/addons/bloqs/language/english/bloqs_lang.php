<?php

$lang = [

    'bloqs_module_name' => BLOQS_NAME,
    'bloqs_module_home' => 'Bloqs Configuration',
    'bloqs_module_description' => 'A modular content add-on for ExpressionEngine 3',

    'bloqs_max_input_vars' => 'PHP <code>max_input_vars</code> setting is too low',
    'bloqs_max_input_vars_description' => 'Bloqs fields with a lot of bloqs assigned to an entry may not save correctly. It is recommended to change your <a href="https://www.php.net/manual/en/info.configuration.php#ini.max-input-vars"><code>max_input_vars</code></a> setting to at least %d or higher.',

    'bloqs_fieldsettings_associateblocks' => 'Bloqs',
    'bloqs_fieldsettings_associateblocks_desc' => 'Select which bloqs to assign to this field and display in the menu. If you\'re using bloq groups, 
        these bloqs will appear in the order you defined, but within their own group.',
    'bloqs_fieldsettings_associateblocks_desc_continued' => ' If a checkbox is disabled, it means it can not be removed as long as it is used by an entry.
        This <a href="https://docs.boldminded.com/bloqs/docs/configuration/hidden-config-options#bloq-usage-and-field-assignments">can be disabled</a>, but it is not recommended or supported due to potential content loss. If a bloq is enabled and unchecked, but has a "Used in X entries" identifier, then it is probably used in a component bloq.',
    'bloqs_fieldsettings_no_blocks_defined' => 'No bloqs have been defined. Bloqs must be defined before being associated with a field.',
    'bloqs_fieldsettings_manage_block_definitions' => 'Edit Bloqs',
    'bloqs_fieldsettings_template_code' => 'Template Code',
    'bloqs_fieldsettings_template_code_desc' => 'This is a basic example of template code necessary to render this field. 
        Copy and paste this into your template file to get started. You will probably need to modify this to meet your project needs.',
    'bloqs_fieldsettings_template_code_desc_generators' => 'If you make changes to the bloqs assigned to this field you will need to save this field\'s settings to receive updated template code.',

    'bloqs_fieldsettings_auto_expand' => 'Auto Expand?',
    'bloqs_fieldsettings_auto_expand_desc' => 'You may have all bloqs display in expanded mode when editing an entry.',

    'bloqs_fieldsettings_menu_grid_display' => 'Display add bloq menu as grid?',
    'bloqs_fieldsettings_menu_grid_display_desc' => 'Display the add bloq menu options as grid, otherwise the options will 
        be displayed vertically in a list (this is the default behavior). Grid display generally works best when you assign preview images or icons to the bloq.',

    'bloqs_fieldsettings_nestable' => 'Nestable?',
    'bloqs_fieldsettings_nestable_desc' => 'Should this field allow nestable bloqs?',

    'bloqs_fieldsettings_associatecomponents' => 'Bloq Components',
    'bloqs_fieldsettings_associatecomponents_desc' => 'Select the bloq components to use for this field.',
    'bloqs_fieldsettings_manage_component_definitions' => 'Edit Bloq Components',
    'bloqs_fieldsettings_no_components_defined' => 'No bloq components have been defined.',

    'bloqs_group_add' => 'New Group',
    'bloqs_blockgroup_name' => 'Bloq Group',
    'bloqs_blockgroup_name_info' => 'Give the group a name. This will appear in the filter menu when you add a new bloq to the page.',
    'bloqs_blockgroup_order' => 'Display Order',
    'bloqs_blockgroup_order_info' => 'Set a display order. The higher the number, the more priority it has and will appear higher in the filter menu.',
    'bloqs_blockgroup_manage' => 'Manage',
    'bloqs_block_groups_title' => 'Bloq Groups',
    'bloqs_groups_no_results' => 'No groups exist. Create your first group to start organizing your bloqs.',
    'bloqs_blockgroup_success_title' => 'Bloq Group Saved!',
    'bloqs_blockgroup_alert_delete_title' => 'Bloq Group Deleted!',
    'bloqs_blocks_in_group' => 'Bloqs in this group',

    'bloqs_block_components_title' => 'Bloq Components',
    'bloqs_components_no_results' => 'No components exist. Create your first component.',
    'bloqs_block_component_success_title' => 'Bloq Component Saved!',
    'bloqs_block_component_alert_delete_title' => 'Bloq Component Deleted!',
    'bloqs_blockdefinitions_component_usage_description' => 'The <b>%s</b> bloq is used in the following bloq components.',
    'bloqs_component_add' => 'New Bloq Component',
    'bloqs_component_manage' => 'Manage',
    'bloqs_component_name' => 'Bloq Component',

    'bloqs_blockdefinition_component_is_component' => 'Is this bloq a component?',
    'bloqs_blockdefinition_component_is_component_info' => 'A component contains multiple bloqs inserted into an entry as a single unit. When enabled the component builder will be displayed below. The bloqs defined below will always be children of this bloq. <b>For this reason a component bloq should only be assigned to a Bloqs field that is set to Nestable</b>.',
    'bloqs_blockdefinition_component_is_editable' => 'Is this bloq component editable?',
    'bloqs_blockdefinition_component_is_editable_info' => 'An editable bloq component means the bloqs are inserted as regular bloqs, and can be individually cloned, deleted, or re-ordered in the publish page. A non-editable bloq component means the user can only clone bloqs within the component if individually enabled, and they can not delete or re-order individual bloqs you define below that make up the component.',
    'bloqs_blockdefinition_component_builder' => 'Component Builder',
    'bloqs_blockdefinition_component_builder_info' => 'Use the field below to add bloqs to your component.',
    'bloqs_blockdefinition_component_builder_field' => 'Component bloqs',
    'bloqs_blockdefinition_component_builder_field_info' => '',

    'bloqs_components_overview' => 'Bloq components are a collection of bloqs that can be inserted into an entry as a group. Using bloq components effectively can greatly increase the speed at which your users can create content. You can find out more about bloq components in the <a href="%s">documentation</a>.',
    'bloqs_groups_overview' => 'Bloq groups are a way to organize your bloqs into logical groups. If you have several bloqs that create social media content, <i>Social Media</i> may make a good group name. Groups are only used in the add bloq menu inside the control panel when editing an entry. They are not used on the front-end. You can find out more about bloq groups in the <a href="%s">documentation</a>',

    'bloqs_blockdefinitions_title' => 'Bloqs',
    'bloqs_blockdefinitions_name' => 'Bloq',
    'bloqs_blockdefinitions_shortname' => 'Short Name',
    'bloqs_blockdefinitions_manage' => 'Manage',
    'bloqs_blockdefinitions_edit' => 'Edit',
    'bloqs_blockdefinitions_clone' => 'Clone',
    'bloqs_blockdefinitions_delete' => 'Delete',
    'bloqs_blockdefinitions_add' => 'New Bloq',
    'bloqs_definitions_no_results' => 'No bloqs exist. Create your first bloq to get started.',

    'bloqs_blockdefinitions_usage' => 'Usage',
    'bloqs_blockdefinitions_usage_description' => 'The <b>%s</b> bloq is used in the following entries.',
    'bloqs_usage_no_results' => 'This bloq is not used in any entries.',

    'bloqs_atomdefinition_missing' => 'Bloqs unable to create the requested atom: %s. This is probably caused by a missing ft.%s.php file, or the fieldtype is not installed.',
    'bloqs_blockdefinition_missing' => 'Bloqs unable to find the requested bloq definition with the ID #%s.',

    'bloqs_blockdefinition_title' => '', // Not used; use the name of the bloq definition instead

    'bloqs_field_has_deprecated_message' => 'This field has deprecated bloqs assigned to it. Deprecated bloqs should be replaced with their suggested changes.',

    // Bloqs Definition View
    'bloqs_blockdefinition_settings' => 'Bloq Settings',
    'bloqs_blockdefinition_name' => 'Name',
    'bloqs_blockdefinition_name_info' => 'This is the name that will appear in the PUBLISH page',
    'bloqs_blockdefinition_name_note' => '',
    'bloqs_blockdefinition_shortname' => 'Short Name',
    'bloqs_blockdefinition_shortname_info' => 'Single word, no spaces. Underscores and dashes allowed',
    'bloqs_blockdefinition_shortname_note' => '',
    'bloqs_blockdefinition_shortname_invalid' => 'The shortname must be a single word with no spaces. Underscores and dashes are allowed.',
    'bloqs_blockdefinition_shortname_inuse' => 'The shortname provided is already in use.',
    'bloqs_blockdefinition_field_header' => 'Atoms (fields)',
    'bloqs_blockdefinition_instructions' => 'Instructions',
    'bloqs_blockdefinition_instructions_info' => 'Instructions for authors on how or what to enter into this field when submitting an entry.',
    'bloqs_blockdefinition_instructions_note' => '',
    'bloqs_blockdefinition_deprecated' => 'Deprecated?',
    'bloqs_blockdefinition_deprecated_message' => 'This bloq is deprecated.',
    'bloqs_blockdefinition_deprecated_info' => 'When a bloq is marked as deprecated, it will still display in your entries in the CP, and on the front-end, 
        but will not be available in the Add Bloq menu to assign it to an entry. Entries that contain deprecated bloqs will display a warning message, but will not prevent saving the entry.',
    'bloqs_blockdefinition_deprecated_note' => 'Why is this bloq deprecated?',
    'bloqs_blockdefinition_deprecated_note_info' => 'Add a short note describing why this bloq is deprecated, and recommended options going forward.',
    'bloqs_blockdefinition_preview_image' => 'Preview Image',
    'bloqs_blockdefinition_preview_image_info' => 'Add a small image or icon to help content editors visualize the bloq. Image will be displayed at 50x50 pixels.',
    'bloqs_blockdefinition_preview_icon' => 'Preview Icon',
    'bloqs_blockdefinition_preview_icon_info' => 'Select an icon to help content editors visualize the bloq. If an image and icon are added, the icon will take precedence.',
    'bloqs_blockdefinition_submit' => 'Save',
    'bloqs_blockdefinition_alert_title' => 'Cannot Create Bloq Type',
    'bloqs_blockdefinition_alert_message' => 'We were unable to create this bloq, please review and fix errors below.',
    'bloqs_blockdefinition_alert_unique' => 'Bloq shortname cannot match other bloqs or channel field shortnames.',
    'bloqs_blockdefinition_success_title' => 'Bloq Saved!',
    'bloqs_blockdefinition_success_title_info' => 'Now that your bloq is saved, be sure to add it to a field!',
    'bloqs_blockdefinition_alert_delete_title' => 'Bloq Deleted!',

    'bloqs_blockdefinition_group' => 'Group',
    'bloqs_blockdefinition_group_info' => 'Select a group that this bloq belongs to better organize and present bloq choices to content editors.',

    'bloqs_blockdefinition_nestable_section' => 'Nesting Options',
    'bloqs_blockdefinition_nesting_root' => 'Nesting Restrictions',
    'bloqs_blockdefinition_nesting_root_info' => 'Choose some basic nesting restrictions for this bloq.',
    'bloqs_blockdefinition_nesting_no_children' => 'Can have children?',
    'bloqs_blockdefinition_nesting_no_children_info' => 'This bloq can have child bloqs, regardless of its nesting level. If a bloq is allowed to have children, it can not be a component bloq. The component definition supersedes any children rules.',
    'bloqs_blockdefinition_nesting_child_of' => 'Parents',
    'bloqs_blockdefinition_nesting_child_of_info' => 'This bloq can only be a child of the selected bloqs. If no bloqs are selected, then it can be a child of any bloq. 
        A bloq can not be a child of another bloq and designated as a root bloq at the same time.',
    'bloqs_blockdefinition_nesting_description_title' => 'Tip: nesting rules only apply if this bloq is assigned to a custom field set to Nestable.',
    'bloqs_blockdefinition_nesting_description' => 'Adding nesting rules to this bloq does not inherently make the custom field this bloq is assigned to nestable. 
        If you find that a Bloqs custom field on your entry publish page is not letting you nest bloqs even if you have defined nesting rules below, 
        please double check the settings for the custom field this bloq is assigned to and ensure that the Nestable setting is turned on. 
        A bloq with nesting rules can also be assigned to a Bloqs custom field that does not have nesting enabled.',

    'bloqs_blockdefinition_nesting_exact_children' => 'Exact Children',
    'bloqs_blockdefinition_nesting_exact_children_info' => 'This bloq must have exactly X child bloqs. This does not include ancestors. 0 value means no restriction.',
    'bloqs_blockdefinition_nesting_min_children' => 'Minimum Children',
    'bloqs_blockdefinition_nesting_min_children_info' => 'This bloq must have at least X child bloqs. This does not include ancestors. 0 value means no restriction',
    'bloqs_blockdefinition_nesting_max_children' => 'Maximum Children',
    'bloqs_blockdefinition_nesting_max_children_info' => 'This bloq must have no more than X child bloqs. This does not include ancestors. 0 value means no restriction',

    'bloqs_blockdefinition_atomdefinition_type' => 'Type',
    'bloqs_blockdefinition_atomdefinition_name' => 'Name',
    'bloqs_blockdefinition_atomdefinition_shortname' => 'Short Name',
    'bloqs_blockdefinition_atomdefinition_instructions' => 'Instructions',
    'bloqs_blockdefinition_atomdefinition_extra' => 'Is this data...',
    'bloqs_blockdefinition_atomdefinition_extra_required' => 'Required?',
    'bloqs_blockdefinition_atomdefinition_extra_search' => 'Searchable?',
    'bloqs_blockdefinition_atomdefinition_settings' => 'Settings',
    'bloqs_blockdefinition_atomdefinition_alert_title' => 'Invalid Bloq Configuration',
    'bloqs_blockdefinition_atomdefinition_alert_message' => 'An error was encountered. Please review and fix the issues highlighted below.',

    'bloqs_invalid_atom_name' => 'Atom name can\'t be the same as it\'s parent bloq name',
    'bloqs_invalid_secret_key' => 'Bloqs secret key was invalid. This was likely a double POST attempt. Check for possible errors in field #%d in entry #%d. User: #%d',

    'bloqs_atomdefinition_ft_missing' => 'Missing definition %s of fieldtype %s.',
    'bloqs_atomdefinition_ft_missing_unknown' => 'Missing definition for unknown fieldtype. This is potentially not good, and difficult to diagnose. Was a row from your exp_blocks_atomdefinition table removed unintentionally?',

    'bloqs_error_generic_title' => 'Bloqs Save Error',
    'bloqs_error_generic_desc' => 'An error has occurred while saving this entry. Please ask your site administrator to check the developer logs.',
    'bloqs_error_critical_title' => 'Bloqs Save Critical Error',
    'bloqs_error_critical_desc' => 'A Bloqs field potentially did not save correctly. Please ask your site administrator to check the developer logs.',
    'bloqs_error_criticial_dev_log' => 'Bloqs field %d on entry %d potentially did not save correctly. Last edited on %s by member #%s',
    'bloqs_error_desc_more' => 'In the mean time, please double check the content in the entry you just saved for correctness.',

    'bloqs_confirmdelete_title' => 'Delete Bloq Definition',
    'bloqs_confirmdelete_content' => 'Are you sure you want to permanently delete this Bloq Definition?',
    'bloqs_confirmdelete_submit' => 'Delete',

    'bloqs_validation_error' => 'There was an error in one or more bloqs',
    'bloqs_field_required' => 'This field is required',
    'bloqs_field_shortname_not_unique' => 'Short name cannot match name of an existing bloq',

    'bloqs_nesting_error_no_close_tags' => 'An error occurred a nested Bloq field, probably because you forgot to add the {close:[block_name]}{/close:[block_name]} tag pair. <a href="https://eebloqs.com/documentation/nesting">Please refer to the documentation</a>.',
    'bloqs_missing_tag_pair' => 'Attempting to render a block from entry #%s, but the template tag pair is missing. Add {%s}{/%s} to your %s template to correct this error.',

    'bloqs_license' => 'License',
    'bloqs_license_name' => 'License Key',
    'bloqs_license_desc' => 'Enter your license key from boldminded.com, or the expressionengine.com store. If you purchased from expressionengine.com you need to <a href="https://boldminded.com/claim">claim your license</a>.',

    'bloqs_release_notes' => 'Release Notes',

    'bloqs_deprecated_close_tags' => 'It looks like you\'re using the deprecated {close:[block_name]}{/close:[block_name]} tag pairs in a Bloqs field. It is recommended to switch to the {bloqs:children} tag. The {close:[block_name]}{/close:[block_name]} tag pair logic will be removed in a future version of Bloqs',

    'bloqs_confirm_removal_cloneable_desc' => 'You can\'t remove the last cloneable bloq in a component.',
];
