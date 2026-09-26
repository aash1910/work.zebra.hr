<?php

namespace EEHarbor\Visitor\Hook;

use EllisLab\ExpressionEngine\Library\CP\URL;

/**
 * Abstract Hook Class
 *
 * @package         EEHarbor_Visitor
 * @author          EEHarbor <https://www.eeharbor.com> - Lead Developer @ Parscale Media
 * @copyright       Copyright (c) 2007-2016 Parscale Media <https://www.parscale.com>
 * @license         https://www.eeharbor.com/license/
 * @link            https://www.eeharbor.com
 */
class CoreBoot extends AbstractHook
{

    /**
     * 'core_Boot' hook.
     *
     * @param  obj $session The session object
     * @return void
     */
    public function execute()
    {
        if (REQ == 'CP') {
            return false;
        }

        //---
        // From this point forward it's only for PAGE/ACTION requests
        //---

        $globalVars =& ee()->config->_global_vars;
        $globalVars['current_uri_string'] = ee()->uri->uri_string;

        //----------------------------------------
        // Logged in?
        //----------------------------------------
        $member_id = ee()->session->userdata('member_id');

        if (!empty($member_id)) {
            $member_id = ee()->session->userdata('member_id');

            $globalVars['visitor_member_id'] = $member_id;
            $globalVars['zoo_member_id'] = $member_id;

            // Set the visitor id
            $visitor_id = ee('visitor:Members')->getVisitorId($member_id);
            $globalVars['visitor_id'] = $visitor_id;
            $globalVars['zoo_visitor_id'] = $visitor_id;

            // = GET THE MEMBER DATA AS GLOBAL VARS? =
            if ($visitor_id) {
                $entry = ee('Model')->get('ChannelEntry', $visitor_id)->first();
                $channel = ee('Model')->get('Channel', $entry->channel_id)->first();

                $fields = $this->getAllCustomFields($channel);

                $globalVars['visitor:global:url_title']        = $entry->url_title;
                $globalVars['visitor:global:expiration_date']  = $entry->expiration_date;
                $globalVars['visitor:global:categories_piped'] = '';

                foreach ($fields as $field) {
                    $globalVars["visitor:global:" . $field->field_name]        = $entry->{'field_id_' . $field->field_id};
                    $globalVars["visitor:global:field_id_" . $field->field_id] = $entry->{'field_id_' . $field->field_id};
                }

                $data_cats = ee()->db->query('SELECT cp.cat_id FROM exp_category_posts cp WHERE cp.entry_id  = "' . $visitor_id . '"');

                if ($data_cats->num_rows() > 0) {
                    $visitor_cats = array();
                    foreach ($data_cats->result_array() as $key => $cat) {
                        $visitor_cats[] = $cat['cat_id'];
                    }

                    $globalVars['visitor:global:categories_piped'] = implode('|', $visitor_cats);
                }

                $uploads = ee()->db->select('*')->from('upload_prefs')->get();
                if (isset($uploads) && $uploads->num_rows() > 0) {
                    foreach ($uploads->result() as $row) {
                        $globalVars["filedir_" . $row->id] = $row->url;
                    }
                }
            }
        }

        // Set the visitor channel name
        if (!isset($this->settings['member_channel_name']) || !$this->settings['member_channel_name']) {
            $this->settings['member_channel_name'] = '';

            $q = ee()->db->select('channel_name')->from('channels')->where('channel_id', $this->settings['member_channel_id'])->get();
            if ($q->num_rows() > 0) {
                $this->settings['member_channel_name'] = $q->row('channel_name');
            }
        }

        $globalVars['visitor_channel_name'] = $this->settings['member_channel_name'];
        $globalVars['zoo_visitor_channel_name'] = $this->settings['member_channel_name'];
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
