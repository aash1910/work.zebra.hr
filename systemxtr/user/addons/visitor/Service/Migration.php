<?php

namespace EEHarbor\Visitor\Service;

use EEHarbor\Visitor\FluxCapacitor\FluxCapacitor;
use EllisLab\ExpressionEngine\Library\Data\Collection;

class Migration
{
    public static $site_id;
    public static $channel;
    public static $fieldGroup;
    public static $readyToMigrate = true;

    public static function readyToMigrate()
    {
        return self::$readyToMigrate;
    }

    public static function getSteps()
    {
        self::$site_id = $site_id = ee()->config->item('site_id');

        $steps = array();

        // Find out if there are any templates that contain zoo_visitor tags.
        $tags = ee('Model')->get('Template')->search('template_data', 'zoo_');

        $num_tags = $tags->count();

        if ($num_tags > 0) {
            self::$readyToMigrate = false;

            // Get the template group names to use in our results.
            $template_groups_results = ee()->db->select('site_id, group_id, group_name')->get('template_groups')->result();
            $template_groups = array();

            foreach ($template_groups_results as $template_group) {
                $template_groups[$template_group->group_id] = $template_group->group_name;
            }

            $tags_info = $tags->all();

            $template_names = array();
            foreach ($tags_info as $taginfo) {
                $template_names[] = '<a rel="' . $template_groups[$taginfo->group_id] . '/' . $taginfo->template_name . '" target="_blank" href="' . ee('CP/URL', 'cp/design/template/edit/' . $taginfo->template_id) . '">' . $template_groups[$taginfo->group_id] . '/' . $taginfo->template_name . '</a>';
            }

            sort($template_names);

            $steps['zv_update_template_tags'] = array('status_label' => 'incomplete', 'status' => 'error', 'type' => 'manual', 'msg' => lang('v:zv_template_tags_desc') . '<br /><ul><li>' . implode('</li><li>', $template_names) . '</li></ul>');
        } else {
            $steps['zv_update_template_tags'] = array('status_label' => 'done', 'status' => 'success', 'type' => 'manual', 'msg' => lang('v:zv_template_tags_desc'));
        }

        $steps['zv_remove_module_files'] = array('status_label' => 'done', 'status' => 'success', 'type' => 'manual', 'msg' => lang('v:zv_remove_module_files_desc'));

        if (file_exists(PATH_THIRD . 'zoo_visitor') || file_exists(PATH_THIRD_THEMES . 'zoo_visitor')) {
            self::$readyToMigrate = false;
            $steps['zv_remove_module_files']['status_label'] = 'incomplete';
            $steps['zv_remove_module_files']['status'] = 'error';

            $steps['zv_remove_module_files']['msg'] .= '<ul>';

            if (file_exists(PATH_THIRD . 'zoo_visitor')) {
                $steps['zv_remove_module_files']['msg'] .= '<li>' . lang('v:zv_remove_module_files_module') . PATH_THIRD . 'zoo_visitor</li>';
            }

            if (file_exists(PATH_THIRD_THEMES . 'zoo_visitor')) {
                $steps['zv_remove_module_files']['msg'] .= '<li>' . lang('v:zv_remove_module_files_theme') . PATH_THIRD_THEMES . 'zoo_visitor</li>';
            }

            $steps['zv_remove_module_files']['msg'] .= '</ul>';
        }

        $steps['zv_remove_module_db_row'] = array('status_label' => 'incomplete', 'status' => 'error', 'type' => 'automatic', 'msg' => lang('v:zv_remove_module_db_row_desc'));
        $steps['zv_migrate_settings'] = array('status_label' => 'incomplete', 'status' => 'error', 'type' => 'automatic', 'msg' => lang('v:zv_migrate_settings_desc'));
        $steps['zv_change_fieldtype_name'] = array('status_label' => 'incomplete', 'status' => 'error', 'type' => 'automatic', 'msg' => lang('v:zv_change_fieldtype_name_desc'));
        $steps['zv_change_channel_fields_type'] = array('status_label' => 'incomplete', 'status' => 'error', 'type' => 'automatic', 'msg' => lang('v:zv_change_channel_fields_type_desc'));
        $steps['zv_change_extensions_name'] = array('status_label' => 'incomplete', 'status' => 'error', 'type' => 'automatic', 'msg' => lang('v:zv_change_extensions_name_desc'));

        if (!empty(ee('Addon')->get('Zoo_visitor')) && ee('Addon')->get('Zoo_visitor')->isInstalled()) {
            $steps['zv_remove_module_db_row'] = array('status_label' => 'done', 'status' => 'success', 'type' => 'automatic', 'msg' => lang('v:zv_remove_module_db_row_desc'));
        }

        if (!ee()->db->table_exists('zoo_visitor_settings')) {
            $steps['zv_migrate_settings'] = array('status_label' => 'done', 'status' => 'success', 'type' => 'automatic', 'msg' => lang('v:zv_migrate_settings_desc'));
        }
        return $steps;


        // =======================================
        // = Does Zoo Visitor channel exists =
        // =======================================
        $channel_id = 0;
        $field_group_id = 0;
        $channel = ee('visitor:Channel')->get();

        if ($channel) {
            $steps['channel_installed']['status'] = 'success';
            $steps['channel_installed']['msg'] = lang('v:channel_exists_yes');

            $channel_id = $channel->channel_id;
            $field_group_id = ee('visitor:Channel')->getFieldGroupId();
        }

        // =======================================
        // = Fieldtype linked to channel =
        // =======================================
        $fieldGroup = ee('Model')->get('ChannelField')
            ->filter('field_type', 'visitor')
            ->first();

        if ($fieldGroup) {
            $steps['fieldtype_in_channel']['status'] = 'success';
            $steps['fieldtype_in_channel']['msg'] = lang('v:fieldtype_in_channel_yes');
        }

        // =======================================
        // = linked_with_members =
        // =======================================
        if (ee('visitor:Settings')->settings['member_channel_id'] > 0) {
            $steps['linked_with_members']['status'] = 'success';
            $steps['linked_with_members']['msg'] = lang('v:linked_with_members_yes');
        }

        // =======================================
        // = allow_member_registration =
        // =======================================
        if (ee()->config->item('allow_member_registration') == 'y') {
            $steps['allow_member_registration']['status'] = 'success';
            $steps['allow_member_registration']['msg'] = lang('v:allow_member_registration_yes');
        }

        // =======================================
        // = guest_member_posts =
        // =======================================
        if (version_compare(APP_VER, '6.0', '>=')) {
            $memberGroup = ee('Model')->get('Role', 3)->first();
            if ($memberGroup->has('can_create_entries_channel_id_' . $channel_id)  && $memberGroup->has('can_edit_self_entries_' . $channel_id)) {
                $steps['guest_member_posts']['status'] = 'success';
                $steps['guest_member_posts']['msg'] = lang('v:guest_member_posts_allowed_yes');
            }
        } else {
            $memberGroup = ee('Model')->get('MemberGroup')->filter('site_id', $site_id)->filter('group_id', 3)->first();
            ee()->db->where('channel_id', $channel_id);
            ee()->db->where('group_id', '3');
            $query = ee()->db->get('channel_member_groups');

            if ($query->num_rows() > 0 && $memberGroup->can_create_entries && $memberGroup->can_edit_self_entries) {
                $steps['guest_member_posts']['status'] = 'success';
                $steps['guest_member_posts']['msg'] = lang('v:guest_member_posts_allowed_yes');
            }
        }

        // =======================================
        // = guest_member_created =
        // =======================================
        if (ee('visitor:Settings')->settings['anonymous_member_id'] > 0 && ee('Model')->get('Member', ee('visitor:Settings')->settings['anonymous_member_id'])->first()) {
            $steps['guest_member_created']['status'] = 'success';
            $steps['guest_member_created']['msg'] = lang('v:guest_member_created_yes');

            // =======================================
            // = channel_form_author =
            // =======================================
            $form = ee('Model')->get('ChannelFormSettings')->filter('channel_id', ee('visitor:Settings')->settings['member_channel_id'])->first();
            if ($form && $form->default_author == ee('visitor:Settings')->settings['anonymous_member_id']) {
                $steps['channel_form_author']['status'] = 'success';
                $steps['channel_form_author']['msg'] = lang('v:channel_form_author_yes');
            }
        }

        return $steps;
    }

    public static function migrate()
    {
        $flux = new FluxCapacitor();
        $site_id = ee()->config->item('site_id');
        $errors  = array();
        $success = array();

        ee()->load->dbforge();

        // =====================
        // = Delete Module Row =
        // =====================

        // Delete the zoo_visitor row from the exp_modules table.
        ee()->db->delete('modules', array('module_name' => 'Zoo_visitor'));

        // ====================
        // = Migrate Settings =
        // ====================

        // Load the current Visitor settings.
        $visitorSettings = ee('visitor:Settings')->settings;

        // Load the Zoo Visitor settings and override the values in Visitor's settings.
        if (ee()->db->table_exists('zoo_visitor_settings')) {
            $zooVisitorSettings = ee()->db->get('zoo_visitor_settings')->result();

            if (!empty($zooVisitorSettings)) {
                foreach ($zooVisitorSettings as $zooVisitorSetting) {
                    $visitorSettings[$zooVisitorSetting->var] = $zooVisitorSetting->var_value;
                }
            }

            // Add the "installed" key to our settings as it didn't exist in Zoo Visitor and by migrating, we're "installing" it.
            $visitorSettings['installed'] = 'yes';

            // Save our settings.
            ee('visitor:Settings')->saveModuleSettings($visitorSettings);

            // Delete the existing zoo_visitor_settings table.
            ee()->dbforge->drop_table('zoo_visitor_settings');
        }

        // ========================================
        // = Migrate Activation Membergroup Table =
        // ========================================
        if (ee()->db->table_exists('zoo_visitor_activation_membergroup')) {
            ee()->dbforge->drop_table('visitor_activation_membergroup');
            ee()->dbforge->rename_table('zoo_visitor_activation_membergroup', 'visitor_activation_membergroup');
        }

        // =========================
        // = Change Fieldtype Name =
        // =========================

        // As Visitor is installed, it already has a fieldtype registered so we have to delete that and
        // convert the zoo_visitor fieldtype to visitor. We don't want to delete using the model because
        // we don't want it to cascade if they've already created channel fields, we just want to change
        // the name so the old and new fields will all work as if they were visitor all along.
        ee()->db->delete('fieldtypes', array('name' => 'visitor')); // Do NOT convert to Model

        // We CAN use the model to change the name of the fieldtype.
        $fieldtype = ee('Model')->get('Fieldtype')->filter('name', 'zoo_visitor')->first();

        if (!empty($fieldtype)) {
            $fieldtype->name = 'visitor';
            $fieldtype->save();
        }

        // ==============================
        // = Change Channel Fields Type =
        // ==============================

        // Change all zoo visitor fields "type" to visitor.
        $fields = ee('Model')->get('ChannelField')->filter('field_type', 'zoo_visitor')->all();
        foreach ($fields as $field) {
            $field->field_type = 'visitor';
            $field->save();
        }

        // =================================
        // = Delete Zoo Visitor Extensions =
        // =================================

        // Delete the existing Zoo Visitor extensions.
        $fields = ee('Model')->get('Extension')->filter('class', 'Zoo_visitor_ext')->delete();

        return true;
    }

    public static function createBasicCustomFields()
    {
        $flux = new FluxCapacitor();
        $field_order = 1;
        $site_id = ee()->config->item('site_id');
        $fieldSettingsText = array('field_maxl' => '256', 'field_content_type' => 'all');
        $fieldSettingsRadio = array();
        $fieldSettingsDate = array();

        $fields   = array();
        $fields[] = array('Member Account', 'member_account', 'visitor', '');
        $fields[] = array('First Name', 'member_firstname', 'text', '');
        $fields[] = array('Last Name', 'member_lastname', 'text', '');
        $fields[] = array('Gender', 'member_gender', 'radio', "Male\nFemale");
        $fields[] = array('Birthday', 'member_birthday', 'date', '');

        foreach ($fields as $theField) {
            $field_order++;

            $field = ee('Model')->get('ChannelField')->filter('field_name', $theField[1])->filter('site_id', $site_id)->first();
            if ($field) {
                continue;
            }

            $field = ee('Model')->make('ChannelField');
            $field->site_id = $site_id;

            if ($flux->ver_lt(4)) {
                $field->group_id = self::$fieldGroup->group_id;
            }

            $field->field_name = $theField[1];
            $field->field_label = $theField[0];
            $field->field_type = $theField[2];
            $field->field_list_items = $theField[3];
            $field->field_order = $field_order;
            $field->field_fmt = 'none';
            $field->field_show_fmt = 'n';

            if ($field->field_type == 'text') {
                $field->field_maxl = 256;
                $field->field_content_type = 'all';
                $field->field_settings = $fieldSettingsText;
            } elseif ($field->field_type == 'radio') {
                $field->field_content_type = 'any';
                $field->field_settings = $fieldSettingsRadio;
            } elseif ($field->field_type == 'date') {
                $field->field_content_type = 'any';
                $field->field_settings = $fieldSettingsDate;
            }

            $field->save();

            if ($flux->ver_gte(4)) {
                ee('visitor:FieldGroup')->attachField($field);
            }
        }
    }
}

/* End of file Migration.php */
/* Location: ./system/user/addons/visitor/Service/Migration.php */
