<?php

namespace EEHarbor\ChannelImages\FluxCapacitor\Conduit;

use EEHarbor\ChannelImages\FluxCapacitor\FluxCapacitor;

class GlobalFieldSettings
{
    public $flux;
    public $fieldtype_name;
    public function __construct($fieldtype_name=null)
    {
        $this->flux = new FluxCapacitor;
        $this->fieldtype_name = $fieldtype_name ?: $this->flux->shortname;
    }

    public function getSetting($name)
    {
        $ftSettings = $this->getSettings();
        if ($ftSettings) {
            return @$ftSettings[$name];
        }
        return null;
    }

    public function getSettings()
    {
        $ftSettingsQuery = ee()->db->select('settings')
            ->where('name', $this->fieldtype_name)
            ->get('fieldtypes');

        if ((bool) $ftSettingsQuery->num_rows()) {
            $ftSettings = @unserialize(@base64_decode($ftSettingsQuery->row('settings'))) ?: array();
        } else {
            return null;
        }

        return $ftSettings;
    }

    public function updateSetting($key, $value)
    {
        $ftSettings = $this->getSettings();

        if (!is_null($ftSettings)) {
            $ftSettings[$key] = $value;
            $settingsForDb = array('settings' => base64_encode(serialize($ftSettings)));

            ee()->db->where('name', $this->fieldtype_name)
                ->update('fieldtypes', $settingsForDb);
        } else {
            return false;
        }

        return true;
    }
}
