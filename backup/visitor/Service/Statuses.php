<?php

namespace EEHarbor\Visitor\Service;

use EEHarbor\Visitor\FluxCapacitor\FluxCapacitor;

class Statuses
{
    public $statusGroup = null;
    private $flux;

    public function __construct($addon)
    {
        $this->flux = new FluxCapacitor();
    }

    public function getOrCreateStatus($memberGroup)
    {
        // Check if the status group exists
        $status = $this->getStatus($memberGroup);

        if (!$status) {
            $status = $this->createStatus($memberGroup);
        }

        return $status;
    }

    public function getOrCreateStatusGroup()
    {
        // Null if ee4+
        if ($this->flux->ver_gte(4)) {
            return null;
        }

        // Dont run the queries again if we already did
        if ($this->statusGroup) {
            return $this->statusGroup;
        }

        // Check if the status group exists
        $this->statusGroup = $this->getStatusGroup();

        if (!$this->statusGroup) {
            $this->statusGroup = $this->createStatusGroup();
        }

        return $this->statusGroup;
    }

    public function createStatusGroup()
    {
        if ($this->flux->ver_gte(4)) {
            return null;
        }

        $this->statusGroup = ee('Model')->make('StatusGroup');
        $this->statusGroup->site_id    = ee()->config->item('site_id');
        $this->statusGroup->group_name = 'Visitor Membergroup';
        $this->statusGroup->save();

        ee('visitor:Channel')->attachStatusGroup();

        return $this->statusGroup;
    }

    public function getStatusGroup()
    {
        if ($this->flux->ver_gte(4)) {
            return null;
        }

        // Check if the status group exists
        $this->statusGroup = ee('Model')->get('StatusGroup')
            ->filter('group_name', 'IN', array('Visitor Membergroup', 'Zoo Visitor Membergroup'))
            ->filter('site_id', ee()->config->item('site_id'))
            ->first();

        return $this->statusGroup;
    }

    public function createStatus($memberGroup)
    {
        // This will initialize $this->statusGroup if EE3
        $this->getOrCreateStatusGroup();
        if (version_compare(APP_VER, '6.0', '>=')) {
            $statusLabel = ee('visitor:Members')->formatStatus($memberGroup->name, $memberGroup->getId());
        } else {
            $statusLabel = ee('visitor:Members')->formatStatus($memberGroup->group_title, $memberGroup->group_id);
        }

        $status = ee('Model')->make('Status');
        $status->status       = $statusLabel;
        $status->status_order = '1';
        $status->highlight = '';

        // For EE3, this is good enough. For ee4, we need to add the status to the channel
        if ($this->flux->ver_lt(4)) {
            $status->site_id      = ee()->config->item('site_id');
            $status->group_id     = $this->statusGroup->group_id;
        }

        if (version_compare(APP_VER, '6.0', '>=')) {
            $status->Roles = ee('Model')->get('Role')->all();
        }

        $status->save();

        ee('visitor:Channel')->attachStatus($status);

        return $status;
    }

    public function getStatus($memberGroup)
    {
        // This will initialize $this->statusGroup if EE3
        $this->getOrCreateStatusGroup();
        if (version_compare(APP_VER, '6.0', '>=')) {
            $statusLabel = ee('visitor:Members')->formatStatus($memberGroup->name, $memberGroup->getId());
        } else {
            $statusLabel = ee('visitor:Members')->formatStatus($memberGroup->group_title, $memberGroup->group_id);
        }

        $status = ee('Model')->get('Status')
                    ->filter('status', $statusLabel);

        if ($this->flux->ver_lt(4)) {
            $status = $status->filter('group_id', $this->statusGroup->group_id);
        }

        return $status->first();
    }

    // public function attachStatusToChannel($channel, $status)
    // {
    //     // This will initialize $this->statusGroup if EE3
    //     $this->getOrCreateStatusGroup();
    //     $statusLabel = ee('visitor:Members')->formatStatus($memberGroup->group_title, $memberGroup->group_id);

    //     $status = ee('Model')->get('Status')
    //                 ->filter('status', $statusLabel);

    //     if ($this->flux->ver_lt(4)) {
    //         $status = $status->filter('group_id', $this->statusGroup->group_id);
    //     }

    //     return $status->first();
    // }
}
