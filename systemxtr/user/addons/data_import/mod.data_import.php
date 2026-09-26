<?php

/**
 * Data Import Module Front End File
 *
 * @package		ExpressionEngine
 * @subpackage	Addons
 * @category	Module
 * @author		addonlabs
 * @link		http://addonlabs.com		
 */

class Data_import {
	
	public $return_data;
	
	// ----------------------------------------------------------------

	public function start()
	{
    	ee()->load->library(array('data_import_process'));
    	ee()->data_import_process->start(ee()->TMPL->fetch_param('import'));
	}
}
/* End of file mod.data_import.php */
/* Location: /system/expressionengine/third_party/data_import/mod.data_import.php */