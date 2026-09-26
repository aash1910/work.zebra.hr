<?php

namespace EEHarbor\Visitor\Service;

use EEHarbor\Visitor\FluxCapacitor\FluxCapacitor;

class FieldGroup
{
    public $fieldGroup = null;

    public function __construct($addon)
    {
        $this->flux = new FluxCapacitor();
        $this->site_id = ee()->config->item('site_id');
    }

    public function getOrCreate()
    {
        $this->fieldGroup = $this->get();

        if (!$this->fieldGroup) {
            $this->fieldGroup = $this->create();
        }

        return $this->fieldGroup;
    }

    public function create()
    {
        $this->fieldGroup = ee('Model')->make('ChannelFieldGroup');
        $this->fieldGroup->site_id = $this->site_id;
        $this->fieldGroup->group_name = 'Visitor Fields';
		if (version_compare(APP_VER, '7.3.0', '>=')) {
			$this->fieldGroup->short_name = 'visitor_fields';
		}
        $this->fieldGroup->save();

        return $this->fieldGroup;
    }

    public function get()
    {
        if ($this->fieldGroup) {
            return $this->fieldGroup;
        }

        $this->fieldGroup = ee('Model')->get('ChannelFieldGroup')
            ->filter('group_name', 'IN', array('Visitor Fields', 'Zoo Visitor Fields'))
            ->filter('site_id', $this->site_id)
            ->first();

        return $this->fieldGroup;
    }

    public function attachField($field)
    {
        $this->getOrCreate();

        $this->fieldGroup->ChannelFields[] = $field;
        $this->fieldGroup->ChannelFields->save();
    }
}
