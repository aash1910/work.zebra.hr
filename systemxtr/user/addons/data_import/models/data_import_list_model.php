<?php

class Data_import_list_model extends Db_lib
{
	
	public function __construct()
	{
		parent::__construct();
		$this->table = 'data_import_list';
	}
	
	public function get_settings($import_id)
	{
		$opt['where'] = array('import_id'=>$import_id);
		$data = $this->get_row($opt);
		//echo "<pre>";echo $import_id;print_r($data);exit;
		if (isset($data['settings']) && $data['settings']) {
			$unserialized = unserialize($data['settings']);
			return is_array($unserialized) ? $unserialized : array();
		}
		return array();
	}
}
	


/* End of file data_import_list_model.php */