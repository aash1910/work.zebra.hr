<?php

namespace EEHarbor\Visitor\Tag;

class UpdateForm extends AbstractTag
{
    public function parse()
    {
        if (!$this->settings['member_channel_id']) {
            $this->log('No member channel has been specified, check your Visitor settings');
            return $this->noResultsConditional('no_results');
        }

        $channel = ee('Model')->get('Channel', $this->settings['member_channel_id'])->first();

        if (!$channel) {
            $this->log('The member channel specified in your Visitor settings does not exist.');
            return $this->noResultsConditional('no_results');
        }

        // allow membergroup to update channel entry
        if (version_compare(APP_VER, '6.0', '>=')) {
            $group_id_field = 'role_id';
            $channel_member_groups_table = 'channel_member_roles';
        } else {
            $group_id_field = 'group_id';
            $channel_member_groups_table = 'channel_member_groups';
        }
        ee()->db->where('channel_id', $this->settings['member_channel_id']);
        ee()->db->where($group_id_field, ee()->session->userdata['group_id']);
        $query_cmg = ee()->db->get($channel_member_groups_table);

        if ($query_cmg->num_rows() == 0) {
            ee()->db->set($group_id_field, ee()->session->userdata['group_id']);
            ee()->db->set('channel_id', $this->settings['member_channel_id']);
            ee()->db->insert($channel_member_groups_table);
        }

        //parse the captcha
        $this->tagdata = $this->parseCaptcha($this->tagdata);

        $require_password = $this->param('require_password', '');
        $member_id        = $this->param('member_id', 'current');
        $member_entry_id  = $this->param('member_entry_id', '');
        $username         = $this->param('username', '');

        if (empty($member_entry_id)) {
            if ($username != '') {
                $entry_id = ee('visitor:Members')->getVisitorIdByUsername($username);
            } else {
                $entry_id = ee('visitor:Members')->getVisitorId($member_id);
            }
        } else {
            $entry_id = $member_entry_id;
        }

        if (empty($entry_id)) {
            return $this->noResultsConditional('no_results');
        }

        // =========================
        // = Native members fields =
        // =========================

        $entry = ee('Model')->get('ChannelEntry', $entry_id)->first();

        if (empty($entry)) {
            $this->log('Member Entry not found');
            return $this->noResultsConditional('no_results');
        }

        $channel = ee('Model')->get('Channel', $entry->channel_id)->first();
        $member = ee('Model')->get('Member', $entry->author_id)->first();

        if (empty($channel) || empty($member)) {
            $this->log('Member Entry not found');
            return $this->noResultsConditional('no_results');
        }

        $fields = $this->getAllCustomFields($channel);

        // Map the channel entry data to the field names.
        // Explicitly check for the birthday field and convert that to the user's date format.
        // Convert all other date fields to the user's date/time format.
        foreach ($fields as $field) {
            if ($field->field_name === 'member_birthday') {
                $entry->{$field->field_name} = ee()->localize->format_date(ee()->session->userdata('date_format', ee()->config->item('date_format')), $entry->{'field_id_' . $field->field_id});
            } elseif ($field->field_type == 'date') {
                $entry->{$field->field_name} = ee()->localize->format_date(ee()->localize->get_date_format($entry->{'field_id_' . $field->field_id}), $entry->{'field_id_' . $field->field_id});
            } else {
                $entry->{$field->field_name} = $entry->{'field_id_' . $field->field_id};
            }
        }

        if (!empty($entry->member_birthday) && empty($entry->bday_y) && empty($entry->bday_m) && empty($entry->bday_d)) {
            $birthdayTimestamp = ee()->localize->string_to_timestamp($entry->member_birthday, true, ee()->localize->get_date_format());
            $entry->bday_y = date('Y', $birthdayTimestamp);
            $entry->bday_m = date('m', $birthdayTimestamp);
            $entry->bday_d = date('d', $birthdayTimestamp);
        }

        $vars = array();
        $vars['native:birthday_year']         = $this->birthdayYear($entry->bday_y);
        $vars['native:birthday_month']        = $this->birthdayMonth($entry->bday_m);
        $vars['native:birthday_day']          = $this->birthdayDay($entry->bday_d);
        $vars['native:url']                   = empty($entry->url) ? 'https://' : $entry->url;
        $vars['native:location']              = $entry->location;
        $vars['native:occupation']            = $entry->occupation;
        $vars['native:interests']             = $entry->interests;
        $vars['native:aol_im']                = $entry->aol_im;
        $vars['native:icq']                   = $entry->icq;
        $vars['native:icq_im']                = $entry->icq;
        $vars['native:yahoo_im']              = $entry->yahoo_im;
        $vars['native:msn_im']                = $entry->msn_im;
        $vars['native:bio']                   = $entry->bio;
        $vars['member_birthday']              = $entry->member_birthday;
        $vars['username' ]                    = $member->username;
        $vars['screen_name' ]                 = $member->screen_name;
        $vars['email' ]                       = $member->email;
        $vars['member_group_id' ]             = $member->group_id;

        $this->tagdata = ee()->TMPL->parse_variables_row($this->tagdata, $vars);

        //wrap in channel form tags
        $form = '{exp:channel:form channel_id="' . $this->settings['member_channel_id'] . '" entry_id="' . $entry_id . '"  use_live_url="no" ' . $this->getChannelFormParams() . '}';

        //insert registration trigger
        $form .= '<input type="hidden" name="visitor_error_delimiters" value="' . htmlentities($this->param('error_delimiters', '')) . '">';
        $form .= '<input type="hidden" name="AG" value="' . ee('visitor:Helper')->encryptString($this->param('allowed_groups', '')) . '">';
        $form .= '<input type="hidden" name="visitor_action" id="visitor_action" value="update">';
        $form .= '<input type="hidden" name="title" id="EE_title" value="' . ee('visitor:Members')->getVisitorEntryTitle($member_id) . '">';
        $form .= '<input type="hidden" name="visitor_require_password" id="visitor_require_password" value="' . $require_password . '">';
        $form .= $this->tagdata;

        //wrap in channel form tags
        $form .= '{/exp:channel:form}';

        //if the form hasn't been submitted, remove error: fields (parse empty)
        return (count($_POST) == 0) ? ee()->TMPL->parse_variables_row($form, array($this->getChannelFormVars())) : $form;
    }

    private function getAllCustomFields($channel)
    {
        $fields = array();

        // If this is EE3, we have to grab the fields the old way.
        if (substr(APP_VER, 0, 1) < 4) {
            $fieldq = ee()->db->query('SELECT ch.field_group, cf.field_id, cf.field_name, cf.field_type FROM exp_channels ch, exp_channel_fields cf WHERE ch.channel_id = "' . $this->settings['member_channel_id'] . '" AND cf.group_id = ch.field_group');

            if (isset($fieldq) && $fieldq->num_rows() > 0) {
                foreach ($fieldq->result() as $row) {
                    $fields[] = $row;
                }
            }
        } else {
            $fields = $channel->getAllCustomFields();
        }

        return $fields;
    }
}

/* End of file UpdateForm.php */
/* Location: ./system/user/addons/Visitor/Tag/UpdateForm.php */
