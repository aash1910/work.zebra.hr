<?php

/**
 * Data Import Module Install/Update File
 *
 * @package		ExpressionEngine
 * @subpackage	Addons
 * @category	Module
 * @author		addonlabs
 * @link		http://addonlabs.com		
 */

class Data_import_upd {

	public $version = '3.0.0';

	// ----------------------------------------------------------------

	/**
	 * Installation Method
	 *
	 * @return 	boolean 	TRUE
	 */
	public function install()
	{
		// Create module using Model Service
		$module = ee('Model')->make('Module');
		$module->module_name = 'Data_import';
		$module->module_version = $this->version;
		$module->has_cp_backend = 'y';
		$module->has_publish_fields = 'n';
		$module->save();

		$this->_install_sql_tables();

		return TRUE;
	}

	private function _install_sql_tables()
	{
		ee()->load->dbforge();
		
		// data_import_settings
		ee()->dbforge->add_field(array(
		'settings'				=> array('type' => 'text'),
		));
		ee()->dbforge->create_table('data_import_settings');
		
		// data_import_list
		ee()->dbforge->add_field(array(
		'import_id'			=> array('type' => 'int', 'constraint' => 10, 'unsigned' => TRUE, 'auto_increment' => TRUE),
		'title'					=> array('type' => 'varchar', 'constraint' => 255, 'null' => TRUE),
		'settings'				=> array('type' => 'text'),
		));
		ee()->dbforge->add_key('import_id', TRUE);
		ee()->dbforge->create_table('data_import_list');
	}
	// ----------------------------------------------------------------

	/**
	 * Uninstall
	 *
	 * @return 	boolean 	TRUE
	 */	
	public function uninstall()
	{
		// Get and delete the module using Model Service
		$module = ee('Model')->get('Module')
			->filter('module_name', 'Data_import')
			->first();
		
		if ($module) {
			$module->delete();
		}

		// Drop custom tables
		ee()->load->dbforge();
		ee()->dbforge->drop_table('data_import_settings');
		ee()->dbforge->drop_table('data_import_list');

		return TRUE;
	}

	// ----------------------------------------------------------------

	/**
	 * Module Updater
	 *
	 * @return 	boolean 	TRUE
	 */	
	public function update($current = '')
	{
		if (version_compare($current, '1.4', '<'))
		{
			ee()->db->truncate('data_import_list');
		}
		
		if (version_compare($current, '1.8', '<'))
		{
			ee()->load->dbforge();
			ee()->dbforge->create_table('data_import_process');
			// data_import_list
			ee()->dbforge->add_field(array(
			'process_id'			=> array('type' => 'int', 'constraint' => 10, 'unsigned' => TRUE, 'auto_increment' => TRUE),
			'import_id'			=> array('type' => 'int', 'constraint' => 10, 'unsigned' => TRUE),
			'title'					=> array('type' => 'varchar', 'constraint' => 255, 'null' => TRUE),
			'settings'				=> array('type' => 'text'),
			));
			ee()->dbforge->add_key('import_id', TRUE);
			ee()->dbforge->create_table('data_import_list');
		}
		
		return TRUE;
	}

}
/* End of file upd.data_import.php */
/* Location: /system/expressionengine/third_party/data_import/upd.data_import.php */