<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class VisitorUpdate_41000
{
    public function update()
    {
        if (version_compare(APP_VER, '6.0', '>=')) {
            $channel = ee('visitor:Channel')->get();
            $memberGroup = ee('Model')->get('Role', 3)->first();
            if (empty($memberGroup->AssignedStatuses->pluck('status_id'))) {
                $memberGroup->AssignedStatuses = $channel->Statuses;
                $memberGroup->save();
            }
        }
    }
}
