<?php

namespace EEHarbor\Visitor\Service;

use EEHarbor\Visitor\FluxCapacitor\FluxCapacitor;

class Channel
{
    public $channel = null;
    public $flux;
    public $site_id;

    public function __construct($addon)
    {
        $this->flux = new FluxCapacitor();
        $this->site_id = ee()->config->item('site_id');
    }

    public function getOrCreate()
    {
        // Check if the status group exists
        $this->channel = $this->get();

        if (!$this->channel) {
            $this->channel = $this->create();
        }

        return $this->channel;
    }

    public function create()
    {
        $channel = ee('Model')->make('Channel');
        $channel->site_id       = $this->site_id;
        $channel->channel_name  = 'visitor';
        $channel->channel_title = 'Visitor Members';
        $channel->channel_url   = '';
        $channel->channel_lang  = ee()->config->item('xml_lang');
        $channel->save();

        // =============================================================
        // = Allow guests to post in this channel, member registration =
        // =============================================================
        $group_id_col = version_compare(APP_VER, '6.0', '>=') ? 'role_id' : 'group_id';
        $channel_member_groups_table = version_compare(APP_VER, '6.0', '>=') ? 'channel_member_roles' : 'channel_member_groups';
        ee()->db->where('channel_id', $channel->channel_id);
        ee()->db->where($group_id_col, '3');
        $query = ee()->db->get($channel_member_groups_table);

        if ($query->num_rows() == 0) {
            ee()->db->set($group_id_col, 3);
            ee()->db->set('channel_id', $channel->channel_id);
            ee()->db->insert($channel_member_groups_table);
        }

        $this->channel = $this->get();

        $this->attachStatusGroup();

        return $this->channel;
    }

    public function get()
    {
        if ($this->channel) {
            return $this->channel;
        }

        // Check to see if we have a channel_id already set in settings.
        if (!empty(ee('visitor:Settings')->settings['member_channel_id'])) {
            $this->channel = ee('Model')->get('Channel')
                ->filter('channel_id', ee('visitor:Settings')->settings['member_channel_id'])
                ->first();
        } else {
            $this->channel = ee('Model')->get('Channel')
                ->filter('channel_name', 'IN', array('zoo_visitor', 'visitor'))
                ->filter('site_id', $this->site_id)
                ->first();
        }

        return $this->channel;
    }

    public function attachStatusGroup()
    {
        if ($this->flux->ver_gte(4)) {
            return null;
        }

        $this->getOrCreate();
        $this->channel->status_group = ee('visitor:Statuses')->getOrCreateStatusGroup();
        $this->channel->save();
    }

    public function attachStatus($status)
    {
        // We dont need to do this in EE3
        if ($this->flux->ver_lt(4)) {
            return null;
        }

        $this->getOrCreate();

        // if it's already a status, don't try to do it again
        foreach ($this->channel->Statuses as $channelStatus) {
            if ($channelStatus->status_id == $status->status_id) {
                return true;
            }
        }

        $this->channel->Statuses[] = $status;
        $this->channel->save();
    }

    public function attachFieldGroup($fieldGroup)
    {
        $this->getOrCreate();

        if ($this->flux->ver_lt(4)) {
            $this->channel->field_group = $fieldGroup->group_id;
        } else {
            $this->channel->FieldGroups = $fieldGroup;
        }
        $this->channel->save();
    }

    public function getFieldGroupId()
    {
        $this->channel = $this->getOrCreate();

        if ($this->flux->ver_lt(4)) {
            return $this->channel->field_group;
        } elseif (!empty($this->channel->FieldGroups->pluck('group_id'))) {
            return $this->channel->FieldGroups->first()->group_id;
        }

        return null;
    }
}
