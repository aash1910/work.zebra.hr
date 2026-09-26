<?php

// This is what we have to do for EE2 support
require_once 'addon.setup.php';

use EEHarbor\Fieldpack\FluxCapacitor\FluxCapacitor;
use EEHarbor\Fieldpack\FluxCapacitor\Base\Upd;

/**
 * Fieldpack Update
 *
 * @package   Fieldpack
 * @author    EEHarbor <help@eeharbor.com>
 * @copyright Copyright (c) 2016 EEHarbor
 */
class Fieldpack_upd extends Upd
{
    public $version;
    public $flux;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Install
     */
    public function install()
    {
        ee()->load->dbforge();

        // -------------------------------------------
        //  Add row to exp_modules
        // -------------------------------------------

        ee()->db->insert('modules', array(
            'module_name'        => "Fieldpack",
            'module_version'     => $this->flux->getConfig('version'),
            'has_cp_backend'     => 'n',
            'has_publish_fields' => 'n',
        ));

        return true;
    }

    /**
     * Update
     */
    public function update($current = '')
    {
        return true;
    }

    /**
     * Uninstall
     */
    public function uninstall()
    {
        ee()->load->dbforge();

        // routine EE table cleanup

        ee()->db->select('module_id');
        $module_id = ee()->db->get_where('modules', array('module_name' => 'Fieldpack'))->row('module_id');

        $module_member_groups_table = version_compare(APP_VER, '6.0', '>=') ? 'module_member_roles' : 'module_member_groups';
        ee()->db->where('module_id', $module_id);
        ee()->db->delete($module_member_groups_table);

        ee()->db->where('module_name', 'Fieldpack');
        ee()->db->delete('modules');

        ee()->db->delete('fieldtypes', array('name' => 'fieldpack_checkboxes'));
        ee()->db->delete('fieldtypes', array('name' => 'fieldpack_dropdown'));
        ee()->db->delete('fieldtypes', array('name' => 'fieldpack_list'));
        ee()->db->delete('fieldtypes', array('name' => 'fieldpack_multiselect'));
        ee()->db->delete('fieldtypes', array('name' => 'fieldpack_pill'));
        ee()->db->delete('fieldtypes', array('name' => 'fieldpack_radio_buttons'));
        ee()->db->delete('fieldtypes', array('name' => 'fieldpack_switch'));

        return true;
    }
}
