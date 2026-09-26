<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Customresources Update/Install File
 */
class Customresources_upd
{
    public $version = '2.0.0';

    /**
     * Install the module
     */
    public function install()
    {
        $data = array(
            'module_name' => 'Customresources',
            'module_version' => $this->version,
            'has_cp_backend' => 'n',
            'has_publish_fields' => 'n'
        );

        ee()->db->insert('modules', $data);

        return true;
    }

    /**
     * Uninstall the module
     */
    public function uninstall()
    {
        ee()->db->where('module_name', 'Customresources');
        ee()->db->delete('modules');

        return true;
    }

    /**
     * Update the module
     */
    public function update($current = '')
    {
        if ($current == $this->version) {
            return false;
        }

        // Add any version-specific updates here
        
        return true;
    }
}
