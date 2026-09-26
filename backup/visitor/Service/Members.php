<?php

namespace EEHarbor\Visitor\Service;

use EEHarbor\Visitor\FluxCapacitor\FluxCapacitor;

class Members
{
    public $site_id;
    public $channel_id;
    public $field_group_id;
    public $flux;

    public function __construct($addon)
    {
        $this->flux = new FluxCapacitor();

        $this->setChannelAndFieldGroup();
        $this->site_id = ee()->config->item('site_id');
    }

    public function getMemberId($entry_id)
    {
        if (!$entry_id) {
            return false;
        }

        $entry = ee('Model')->get('ChannelEntry', $entry_id)->fields('author_id')->first();

        if (!$entry) {
            return false;
        }

        // If the entry was found, check if the author exists
        $member = ee('Model')->get('Member', $entry->author_id)->fields('member_id')->first();

        if (!$member) {
            return false;
        }

        return $member->member_id;
    }

    public function getVisitorId($member_id = 'current')
    {
        if ($member_id == 'current' || empty($member_id)) {
            if (!isset(ee()->session->userdata)) {
                //no session is available, this will handle trying to get the gloval var zoo_visitor_id when user is not logged in;
                $member_id = 0;
            } else {
                $member_id = ee()->session->userdata('member_id');
            }
        }

        if (!$member_id) {
            return false;
        }

        $channel_id = ee('visitor:Settings')->settings['member_channel_id'];
        if (!$channel_id) {
            return false;
        }

        $visitor_query = ee()->db->select('entry_id')->where('author_id', $member_id)->where('channel_id', $channel_id)->order_by('entry_id', 'desc')->limit(1)->get('channel_titles');

        if ($visitor_query->num_rows() == 0) {
            return false;
        }

        return $visitor_query->row()->entry_id;
    }

    public function getVisitorIdByUsername($username)
    {
        if (!$username) {
            return false;
        }

        $channel_id = ee('visitor:Settings')->settings['member_channel_id'];
        if (!$channel_id) {
            return false;
        }

        ee()->db->select('ct.entry_id');
        ee()->db->from('members AS m');
        ee()->db->join('channel_titles AS ct', "ct.author_id = m.member_id", 'left');
        ee()->db->where('m.username', $username);
        ee()->db->where('ct.channel_id', $channel_id);
        $query = ee()->db->get();

        if ($query->num_rows() == 0) {
            return false;
        }

        return $query->first_row()->entry_id;
    }

    public function getVisitorEntryTitle($member_id = 'current')
    {
        if ($member_id == 'current') {
            $member_id = ee()->session->userdata['member_id'];
        }

        if (!$member_id) {
            return false;
        }

        $channel_id = ee('visitor:Settings')->settings['member_channel_id'];
        if (!$channel_id) {
            return false;
        }

        ee()->db->select('title');
        ee()->db->where('author_id', $member_id);
        ee()->db->where('channel_id', $channel_id);
        ee()->db->order_by('entry_id', 'desc');
        ee()->db->limit(1);
        $visitor_query = ee()->db->get('channel_titles');

        if (!$visitor_query->num_rows()) {
            return false;
        }

        return $visitor_query->first_row()->title;
    }

    public function registerMemberSimple($data = false)
    {
        if (!$data) {
            $data = $_POST;
        }

        $member = ee('Model')->make('Member');
        $member->group_id        = (!$data['group_id']) ? 2 : $data['group_id'];
        $member->screen_name     = ($data['screen_name']) ? $data['screen_name'] : $data['username'];
        $member->username        = $data['username'];
        $member->password        = $data['password'];
        $member->email           = $data['email'];
        $member->ip_address      = ee()->input->ip_address();
        $member->join_date       = ee()->localize->now;
        $member->language        = ee()->config->item('deft_lang');
        $member->timezone        = ee()->config->item('default_site_timezone');
        $member->date_format     = ee()->config->item('date_format');
        $member->time_format     = ee()->config->item('time_format');
        $member->include_seconds = ee()->config->item('include_seconds');

        foreach ($member->getDisplay()->getFields() as $field) {
            if ($field->get('m_field_reg') == 'y' or $field->isRequired()) {
                /*
                if (ee()->input->post('m_field_id_' . $row['m_field_id']) !== FALSE) {
                    $cust_fields['m_field_id_' . $row['m_field_id']] = $this->input->post('m_field_id_' . $row['m_field_id'], TRUE);
                }
                */
            }
        }

        // Now that we know the password is valid, hash it
        ee()->load->library('auth');
        $hashed_password = ee()->auth->hash_password($member->password);
        $member->password = $hashed_password['password'];
        $member->salt = $hashed_password['salt'];

        // -------------------------------------------
        // 'cp_members_member_create_start' hook.
        //  - Take over member creation when done through the CP
        //  - Added 1.4.2
        //
        ee()->extensions->call('cp_members_member_create_start');
        if (ee()->extensions->end_script === true) {
            return;
        }
        //
        // -------------------------------------------

        $member->save();

        // -------------------------------------------
        // 'cp_members_member_create' hook.
        //  - Additional processing when a member is created through the CP
        //
        ee()->extensions->call('cp_members_member_create', $member->getId(), $member->getValues());
        if (ee()->extensions->end_script === true) {
            return;
        }
        //
        // -------------------------------------------

        ee()->logger->log_action(lang('new_member_added') . NBS . $member->username);
        ee()->stats->update_member_stats();

        return $member->getId();
    }

    public function getMemberGroups()
    {
        $memberGroups = array();
        if (version_compare(APP_VER, '6.0', '>=')) {
            $mgroups = ee('Model')->get('Role');
        } else {
            $mgroups = ee('Model')->get('MemberGroup');
        }

        // Member groups assignment
        if (ee()->session->userdata('group_id') != 1) {
            $mgroups->filter('is_locked', 'n');
        }

        if (version_compare(APP_VER, '6.0', '>=')) {
            $mgroups = $mgroups->fields('role_id', 'name')->all();
        } else {
            $mgroups = $mgroups->fields('group_id', 'group_title')->all();
        }

        foreach ($mgroups as $group) {
            // If the current user is not a Super Admin
            // we'll limit the member groups in the list
            if ($group->getId() == 1 && ee()->session->userdata('group_id') != 1) {
                continue;
            }

            $memberGroups[$group->getId()] = version_compare(APP_VER, '6.0', '>=') ? $group->name : $group->group_title;
        }

        return $memberGroups;
    }

    public function syncMemberStatus($member_id, $entry_id = false)
    {
        if (!$member_id) {
            return false;
        }

        $member = ee('Model')->get('Member', $member_id)->first();
        if (!$member) {
            return false;
        }

        $group_id = $member->group_id;

        if (!$entry_id) {
            $entry_id = $this->getVisitorId($member_id);
        }

        if ($entry_id) {
            $this->updateMemberStatus($entry_id, $member_id, $group_id);
        }
    }

    public function updateMemberStatus($entry_id, $member_id, $group_id)
    {
        if (ee('visitor:Settings')->settings['membergroup_as_status'] == 'no') {
            return;
        }

        if (version_compare(APP_VER, '6.0', '>=')) {
            $group = ee('Model')->get('Role', $group_id)->first();
        } else {
            $group = ee('Model')->get('MemberGroup', $group_id)->first();
        }
        if (!$group) {
            return;
        }

        $status = ee('visitor:Statuses')->getOrCreateStatus($group);

        ee()->db->set('status', $status->status)->where('entry_id', $entry_id)->update('channel_titles');

        if ($this->flux->ver_gte(4)) {
            ee()->db->set('status_id', $status->status_id)->where('entry_id', $entry_id)->update('channel_titles');
        }
    }

    public function updateScreenName($member_id = 'current', $entry_id = false, $data = array())
    {
        if ($member_id == 'current') {
            $member_id = ee()->session->userdata('member_id');
        }
        if (!$member_id) {
            return false;
        }

        $member = ee('Model')->get('Member', $member_id)->first();
        if (!$member) {
            return false;
        }

        $group_id = $member->group_id;

        if (!$entry_id) {
            $entry_id = $this->getVisitorId($member_id);
        }

        if (!$entry_id) {
            return false;
        }

        $screen_name_override = '';

        // If the `screen_name` in the `$data` array is not empty, we already validated
        // it and it was passed through directly so just use that!
        if (!empty($data['screen_name'])) {
            $screen_name = $data['screen_name'];
        } else {
            $screen_name = '';

            $entry = ee('Model')->get('ChannelEntry', $entry_id)->first();

            if (!empty($entry)) {
                $screen_name_override = ee('visitor:Settings')->settings['screen_name_override'];
                $screen_name = $screen_name_override;

                foreach ($entry->toArray() as $field_name => $val) {
                    // If this is a custom field, look up the field name so we can replace it as the screen_name uses the field shortname.
                    if (substr($field_name, 0, 9) === 'field_id_' || substr($field_name, 0, 9) === 'field_ft_') {
                        $field_id = substr($field_name, 9);
                        $field_info = ee()->db->get_where('channel_fields', array('field_id' => $field_id))->row();

                        if (!empty($field_info) && !empty($field_info->field_name)) {
                            $field_name = $field_info->field_name;
                        }
                    }

                    if ($val === 'none') {
                        $val = '';
                    }

                    if (strpos($screen_name, '{' . $field_name . '}') !== false && !empty($val)) {
                        $screen_name = str_replace('{' . $field_name . '}', $val, $screen_name);
                    }
                }
            }
        }

        $screen_name_check = trim(str_replace(' ', '', $screen_name));

        if (empty($screen_name_check) || (!empty($screen_name_override) && $screen_name === $screen_name_override)) {
            return false;
        }

        $member->screen_name = $screen_name;
        $member->save();

        return $screen_name;
    }

    public function updateEntryTitle($entry_id, $member_id = false, $fromPost = false)
    {
        if (is_object($entry_id)) {
            $entry = $entry_id;
            $entry_id = $entry->entry_id;
        } else {
            $entry = ee('Model')->get('ChannelEntry', $entry_id)->first();
        }

        $member = null;

        if ($member_id) {
            $member = ee('Model')->get('Member', $member_id)->first();
        } else {
            $member_id = ee('visitor:Members')->getMemberId($entry_id);
            if ($member_id) {
                $member = ee('Model')->get('Member', $member_id)->first();
            }
        }

        if (!$member) {
            return false;
        }

        if (!empty(ee('visitor:Settings')->settings['title_override'])) {
            $title_override = ee('visitor:Settings')->settings['title_override'];
            $title = $title_override;

            foreach ($entry->toArray() as $field_name => $val) {
                $post_field_name = $field_name;

                // If this is a custom field, look up the field name so we can replace it as the title uses the field shortname.
                if (substr($field_name, 0, 9) === 'field_id_' || substr($field_name, 0, 9) === 'field_ft_') {
                    $field_id = substr($field_name, 9);
                    $field_info = ee()->db->get_where('channel_fields', array('field_id' => $field_id))->row();

                    if (!empty($field_info) && !empty($field_info->field_name)) {
                        $field_name = $field_info->field_name;
                    }
                }

                if ($val === 'none') {
                    $val = '';
                }

                if (strpos($title, '{' . $field_name . '}') !== false) {
                    if ($fromPost === true && !empty($_POST[$post_field_name]) && !empty($_POST[$post_field_name])) {
                        $title = str_replace('{' . $field_name . '}', $_POST[$post_field_name], $title);
                    } elseif (!empty($val)) {
                        $title = str_replace('{' . $field_name . '}', $val, $title);
                    }
                }
            }

            $title_check = trim(str_replace(' ', '', $title));

            if (empty($title_check) || $title === $title_override) {
                $title = '';
            }

            // Check if the title is empty. If so, it'll disappear in the
            // EE control panel give it a unique name.
            if (empty($title)) {
                $title = uniqid('N/A ');
            }

            $title = str_replace(
                array(
                    '{member_id}',
                    '{group_id}',
                    '{username}',
                    '{screen_name}'
                ),
                array(
                    $member->member_id,
                    $member->group_id,
                    $member->username,
                    $member->screen_name
                ),
                $title
            );

            // = if custom fields are empty, fall back to screenname  =
            $title = (str_replace(' ', '', $title) == "") ? $member->screen_name : $title;
        } else {
            $title = $member->username;
        }

        $titlecheck = str_replace(' ', '', $title);

        if ($titlecheck != '') {
            $url_title = $this->flux->url_title($title, ee()->config->item('word_separator'), true);
            $url_title = $this->validateUrlTitle($url_title, $title, true, $entry_id, ee('visitor:Settings')->settings['member_channel_id']);

            // =================================================================================================
            // = there is a problem with converting the title to a valid url title (numbers, foreign chars...) =
            // =================================================================================================
            if (!$url_title) {
                $url_title = $member->screen_name;
                $url_title = $this->flux->url_title($url_title, ee()->config->item('word_separator'), true);
                $url_title = $this->validateUrlTitle($url_title, $url_title, true, $entry_id, ee('visitor:Settings')->settings['member_channel_id']);

                if (!$url_title) {
                    $url_title = $member->username;
                    $url_title = $this->flux->url_title($url_title, ee()->config->item('word_separator'), true);
                    $url_title = $this->validateUrlTitle($url_title, $url_title, true, $entry_id, ee('visitor:Settings')->settings['member_channel_id']);

                    if (!$url_title) {
                        $url_title = $member->email;
                        $url_title = $this->flux->url_title($url_title, ee()->config->item('word_separator'), true);
                        $url_title = $this->validateUrlTitle($url_title, $url_title, true, $entry_id, ee('visitor:Settings')->settings['member_channel_id']);
                    }
                }
            }
            //$url_title = $this->uniqueUrlTitle($url_title, $entry_id, ee('visitor:Settings')->settings['member_channel_id']);


            // //set channel entry title and url title
            if ($url_title != false) {
                ee()->db->update('channel_titles', array('title' => $title, 'url_title' => $url_title), 'entry_id = ' . $entry_id);
            }
        }
    }

    protected function uniqueUrlTitle($url_title, $self_id, $type_id = '', $type = 'channel')
    {
        if (empty($type_id)) {
            return false;
        }

        switch ($type) {
            case 'category':
                $table           = 'categories';
                $url_title_field = 'cat_url_title';
                $type_field      = 'group_id';
                $self_field      = 'category_id';
                break;
            default:
                $table           = 'channel_titles';
                $url_title_field = 'url_title';
                $type_field      = 'channel_id';
                $self_field      = 'entry_id';
                break;
        }

        // Field is limited to 75 characters, so trim url_title before querying
        $url_title = substr($url_title, 0, 75);

        if ($self_id != '') {
            ee()->db->where(array($self_field . ' !=' => $self_id));
        }

        ee()->db->where(array($url_title_field => $url_title,
                                   $type_field      => $type_id));
        $count = ee()->db->count_all_results($table);

        if ($count > 0) {
            // We may need some room to add our numbers- trim url_title to 70 characters
            $url_title = substr($url_title, 0, 70);

            // Check again
            if ($self_id != '') {
                ee()->db->where(array($self_field . ' !=' => $self_id));
            }

            ee()->db->where(array($url_title_field => $url_title,
                                       $type_field      => $type_id));
            $count = ee()->db->count_all_results($table);

            if ($count > 0) {
                if ($self_id != '') {
                    ee()->db->where(array($self_field . ' !=' => $self_id));
                }

                ee()->db->select("{$url_title_field}, MID({$url_title_field}, " . (strlen($url_title) + 1) . ") + 1 AS next_suffix", false);
                ee()->db->where("{$url_title_field} REGEXP('" . preg_quote(ee()->db->escape_str($url_title)) . "[0-9]*$')");
                ee()->db->where(array($type_field => $type_id));
                ee()->db->order_by('next_suffix', 'DESC');
                ee()->db->limit(1);
                $query = ee()->db->get($table);

                // Did something go tragically wrong?  Is the appended number going to kick us over the 75 character limit?
                if ($query->num_rows() == 0 or ($query->row('next_suffix') > 99999)) {
                    return false;
                }

                $url_title = $url_title . $query->row('next_suffix');

                // little double check for safety

                if ($self_id != '') {
                    ee()->db->where(array($self_field . ' !=' => $self_id));
                }

                ee()->db->where(array($url_title_field => $url_title,
                                           $type_field      => $type_id));
                $count = ee()->db->count_all_results($table);

                if ($count > 0) {
                    return false;
                }
            }
        }

        return $url_title;
    }

    public function validateUrlTitle($url_title = '', $title = '', $update = false, $entry_id = 0, $channel_id = 0)
    {
        $word_separator = ee()->config->item('word_separator');

        ee()->load->helper('url');

        if (!trim($url_title)) {
            $url_title = $this->flux->url_title($title, $word_separator, true);
        }

        // Remove extraneous characters

        if ($update) {
            ee()->db->select('url_title');
            $url_query = ee()->db->get_where('channel_titles', array('entry_id' => $entry_id));

            if ($url_query->row('url_title') != $url_title) {
                $url_title = $this->flux->url_title($url_title, $word_separator);
            }
        } else {
            $url_title = $this->flux->url_title($url_title, $word_separator);
        }

        // URL title cannot be a number

        if (is_numeric($url_title)) {
            return false;
            //$this->_set_error('url_title_is_numeric', 'url_title');
        }

        // It also cannot be empty

        if (!trim($url_title)) {
            return false;
            //$this->_set_error('unable_to_create_url_title', 'url_title');
        }

        // And now we need to make sure it's unique

        if ($update) {
            $url_title = $this->uniqueUrlTitle($url_title, $entry_id, $channel_id);
        } else {
            $url_title = $this->uniqueUrlTitle($url_title, '', $channel_id);
        }

        // One more safety

        if (!$url_title) {
            return false;
            //$this->_set_error('unable_to_create_url_title', 'url_title');
        }

        // And lastly, we prevent this potentially problematic case

        if ($url_title == 'index') {
            return false;
            //$this->_set_error('url_title_is_index', 'url_title');
        }

        return $url_title;
    }

    /**
     * Update the native EE member fields (this includes the "Custom" member fields)
     * with the values Visitor is storing in the Channel Entry.
     * Because Visitor's registration and profile forms are glorified channel forms,
     * all the data is stored as channel entry data and is not stored in the EE member
     * fields by default so we have to update any of those with our custom data.
     *
     * @param  int  $member_id Member's ID
     * @param  int  $entry_id  Entry ID that corresponds to member's profile entry
     * @return null
     */
    public function updateNativeMemberFields($member_id, $entry_id = false)
    {
        // If we weren't passed an entry_id, try to get it from the member_id.

        if (!$entry_id) {
            $entry_id = $this->getVisitorId($member_id);
        }

        // If we still don't have an entry_id, something has gone awry.
        if (!$entry_id) {
            return false;
        }

        // Grab what EE knows about this member.
        $member = ee('Model')->get('Member', $member_id)->first();



        /**
         * ALRIGHT.. are you paying attention?
         * Everything that follows is JUST so we can create an array of
         * `m_field_id_X` => $what_visitor_has_on_file_for_this_member
         * so we can update the default and custom EE member fields with it.
         */

        /**
         * Custom Member Fields
         * These exist in the exp_member_data table
         */

        // Get data from channel entry instead from post array
        $entry = ee('Model')->get('ChannelEntry', $entry_id)->first();

        $channelFields = $this->getAllCustomFields($entry->channel_id);

        $channelEntryData = array();
        foreach ($channelFields as $channelField) {
            $channelEntryData[$channelField->field_id] = $entry->{'field_id_' . $channelField->field_id};
        }


        /**
         * Grab the values for any custom member fields that exist in both the
         * EE member fields and as a channel field.
         *
         * NOTE: The channel field version will be prefixed with "member_"
         * but it references the same field.
         *
         * Example: "gender" (member field) == "member_gender" (channel field)
         */
        $sql = "SELECT mf.m_field_id AS member_field_id,
            mf.m_field_name,
            cf.field_id AS channel_field_id,
            cf.field_name
            FROM exp_member_fields mf, exp_channel_fields cf
            WHERE cf.field_name = CONCAT('', mf.m_field_name)";

        // Array of all field ids in this entry

        if (version_compare(ee()->config->item('app_version'), '4.0.0', '<')) {
            $field_ids = array();
            foreach ($channelFields as $value) {
                array_push($field_ids, $value->field_id);
            }
        } else {
            $field_ids = $channelFields->pluck('field_id');
        }

        if (count($field_ids) > 0) {
            // This fixes the query when there are multiple fields with a different name. This is a potential issue on MSM sites.
            $field_ids_string = implode(', ', $field_ids);
            $sql .= " AND cf.field_id IN ({$field_ids_string})";
        }

        $memberFieldsThatExistAsChannelFields = ee()->db->query($sql);

        // Now that we have everything ready, let's map our channelEntryData for each custom member field.
        $customMemberData = array();
        if ($memberFieldsThatExistAsChannelFields->num_rows() > 0) {
            foreach ($memberFieldsThatExistAsChannelFields->result() as $memberField) {
                $customMemberData['m_field_id_' . $memberField->member_field_id] = $channelEntryData[$memberField->channel_field_id];
                $customMemberDataName['m_field_name_' . $memberField->member_field_id] = $memberField->m_field_name;
            }
        }

        // ============================================
        // = Check if there is a membergroup selected =
        // ============================================
        $this->checkMemberGroupChange($customMemberData);

        if (count($customMemberData) > 0) {
            $member->set($customMemberData);
            $member->save();
        }

        // Save it if you got it!
        $actual = array();
        for($i = 1; $i < count($customMemberData)+1; ++$i) {
            $member_field = $customMemberDataName['m_field_name_' . $i];
            if (strpos($member_field, 'member_') !== false) {
                $field = substr($member_field, 7);
                $actual[$field] = $customMemberData['m_field_id_' . $i];
            }
        }

        $member->set($actual);
        $member->save();


        /**
         * Default Member Fields
         * These exist in the exp_members table
         */

        // $defaultMemberFieldData = $this->contains_native_member_fields();

        // if ($defaultMemberFieldData !== false) {
        //     ee()->db->where('member_id', $author_id);
        //     ee()->db->update('member_data', $defaultMemberFieldData);
        // }

        // $data = array();

        // // Setup our default and former default field arrays. We're going to merge them together but
        // // are calling them out separately here so that we know they're not all on the members table.
        // $defaultFields = array('signature', 'timezone', 'time_format', 'language', 'avatar', 'photo');
        // $formerDefaultFields = array('bday_y', 'bday_m', 'bday_d', 'birthday', 'url', 'location', 'occupation', 'interests', 'aol_im', 'icq', 'yahoo_im', 'msn_im', 'bio');

        // // Combine the default and formerDefault fields together.
        // $nativeFields = array_merge($defaultFields, $formerDefaultFields);

        // $nativeFieldsMap = array();

        // // Remap our combined fields array with the "member_" prefix to prevent field name collision.
        // foreach ($nativeFields as $field_name) {
        //     $nativeFieldsMap[] = 'member_' . $field_name;
        // }

        // // Get any channel fields that match our "nativeFields" and "formerNativeFields" above.
        // // We prepended "member_" to these fields to prevent field name collision so "birthday" from the array
        // // above will actually be "member_birthday" in the ChannelField result.
        // $channelFields = ee('Model')->get('ChannelField')->filter('field_name', 'IN', $nativeFieldsMap)->all();

        // if ($channelFields) {
        //     // Loop through our channelFields and overwrite their values with our custom values.
        //     foreach ($channelFields as $channelField) {
        //         // Use `isset` here instead of `empty` because we WANT empty values to be saved
        //         // in case a field is updated with a blank value.
        //         if (isset($channelEntryData[$channelField->field_id])) {
        //             $field_value = $channelEntryData[$channelField->field_id];

        //             // Check if we have a curly brace in our field value and only use the part after that.
        //             if (strpos($field_value, '}') !== false) {
        //                 $field_value = substr($field_value, strpos($field_value, '}') + 1);
        //             }

        //             // Save this value to update the native Member fields.
        //             $data[$field_name] = $field_value;
        //         }
                
        //     }

        //     if (isset($data['birthday'])) {
        //         //dropdate passes date as an array
        //         if (is_array($data['birthday'])) {
        //             $data['bday_d'] = $data['birthday'][0];
        //             $data['bday_m'] = $data['birthday'][1];
        //             $data['bday_y'] = $data['birthday'][2];
        //         } else {
        //             $data['bday_d'] = date('j', strtotime($data['birthday']));
        //             $data['bday_m'] = date('n', strtotime($data['birthday']));
        //             $data['bday_y'] = date('Y', strtotime($data['birthday']));
        //         }
        //         unset($data['birthday']);
        //     }

        //     if (isset($data['bday_d']) && isset($data['bday_m']) && is_numeric($data['bday_d']) and is_numeric($data['bday_m'])) {
        //         $year = (isset($data['bday_y']) && $data['bday_y'] != '') ? $data['bday_y'] : date('Y');

        //         ee()->load->helper('date');
        //         $mdays = days_in_month($data['bday_m'], $year);

        //         if ($data['bday_d'] > $mdays) {
        //             $data['bday_d'] = $mdays;
        //         }
        //     }
        // }


        // if (isset($data['avatar'])) {
        //     $data['avatar_filename'] = 'uploads/' . $data['avatar'];
        //     unset($data['avatar']);
        // }
        

        // if (isset($data['photo'])) {
        //     $data['photo_filename'] = $data['photo'];
        //     unset($data['photo']);
        // }

        // exit;
        // if (count($data) > 0) {
        //     $member->set($data);
        //     $member->save();
        // }
    }

    public function getAllCustomFields($channel_id)
    {
        $fields = array();

        if (substr(APP_VER, 0, 1) < 4) {
            $fieldq = ee()->db->query('SELECT ch.field_group, cf.field_id, cf.field_name, cf.field_type FROM exp_channels ch, exp_channel_fields cf WHERE ch.channel_id = "' . $channel_id . '" AND cf.group_id = ch.field_group');

            if (isset($fieldq) && $fieldq->num_rows() > 0) {
                foreach ($fieldq->result() as $row) {
                    $fields[] = $row;
                }
            }
        } else {
            $fields = ee('Model')->get('Channel', $channel_id)->first()->getAllCustomFields();
        }

        return $fields;
    }

    public function validateCurrentPassword($member_id, $current_password)
    {
        ee()->lang->loadfile('myaccount');

        if (ee()->session->userdata('group_id') == 1) {
            //return;
        }

        if (empty($current_password)) {
            return ee()->lang->line('missing_current_password');
        }

        ee()->load->library('auth');



        $authed = ee()->auth->authenticate_id($member_id, $current_password);
        if (strcmp(get_class($authed), "Auth_result") !== 0 or $authed === false) {
            return ee()->lang->line('invalid_password');
        }

        return 'valid';
    }

    public function checkMemberGroupChange(&$data)
    {
        $selected_group_id = '';

        // ============================================
        // = Check if there is a membergroup selected =
        // ============================================
        $allowed_groups = (isset($_POST['AG'])) ? ee('visitor:Helper')->decodeString($_POST['AG']) : '';

        if (isset($_POST['AG']) && isset($_POST['group_id']) && ctype_digit($_POST['group_id']) && $allowed_groups !== '') {
            $group_id_col = version_compare(APP_VER, '6.0', '>=') ? 'role_id' : 'group_id';
            $member_groups_table = version_compare(APP_VER, '6.0', '>=') ? 'exp_roles' : 'exp_member_groups';
            $sql = "SELECT DISTINCT $group_id_col FROM $member_groups_table WHERE $group_id_col NOT IN (1,2,3,4) AND $group_id_col = '" . ee()->db->escape_str($_POST['group_id']) . "'" . ee()->functions->sql_andor_string($allowed_groups, $group_id_col);

            $query = ee()->db->query($sql);
            if ($query->num_rows() > 0) {
                $selected_group_id = $query->row($group_id_col);
            }

            if (isset($_POST['entry_id']) && $_POST['entry_id'] != '0') {
                $data['group_id'] = $selected_group_id;
            } else {
                if (isset($selected_group_id) && is_numeric($selected_group_id)) {
                    if (ee()->config->item('req_mbr_activation') == 'manual' or ee()->config->item('req_mbr_activation') == 'email') {
                        $data['group_id'] = 4; // Pending
                    } else {
                        $data['group_id'] = $selected_group_id;
                    }
                }
            }
        }

        return $selected_group_id;
    }

    protected function setChannelAndFieldGroup()
    {
        $channel_id = 0;
        $field_group_id = 0;

        if (ee('visitor:Settings')->settings['member_channel_id']) {
            $channel = ee('Model')->get('Channel', ee('visitor:Settings')->settings['member_channel_id'])->first();
        } else {
            $channel = ee('visitor:Channel')->get();
        }

        if ($channel) {
            $this->channel_id = $channel->channel_id;
            $this->field_group_id = ee('visitor:Channel')->getFieldGroupId();
            return true;
        }

        return false;
    }

    public function formatStatus($group_title, $group_id)
    {
        return preg_replace("/[^a-z0-9_]/i", '_', $group_title) . '-id' . $group_id;
    }

    // deprecated method for saving native fields based on name m_field_id_x
    public function contains_native_member_fields()
    {
        $native_member_fields      = false;
        $native_member_fields_data = array();
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'm_field_id_') !== false) {
                $native_member_fields_data[$key] = $value;
                $native_member_fields            = true;
            }
        }

        return ($native_member_fields) ? $native_member_fields_data : false;
    }
}

/* End of file Members.php */
/* Location: ./system/user/addons/visitor/Service/Members.php */
