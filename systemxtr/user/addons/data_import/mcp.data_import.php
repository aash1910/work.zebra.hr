<?php

/**
 * Data Import Module Control Panel File
 *
 * @package		ExpressionEngine
 * @subpackage	Addons
 * @category	Module
 * @author		addonlabs
 * @link		http://addonlabs.com		
 */

class Data_import_mcp {
	
	public $return_data;
	
	private $_base_url;
	private $_action_url;
	
	public $sidebar;
	public $import_list;
	public $database_settings;
	public $data_import_usage;
	
	/**
	 * Constructor
	 */
	public function __construct()
	{
		
		$this->_base_url = ee('CP/URL')->make('addons/settings/data_import');
		$this->_action_url = ee('CP/URL')->make('addons/settings/data_import');

		// Create sidebar navigation
		$this->sidebar = ee('CP/Sidebar')->make();
        $this->import_list = $this->sidebar->addHeader(lang('import_list'), ee('CP/URL')->make('addons/settings/data_import/import_list'));
        $this->database_settings = $this->sidebar->addHeader(lang('database_settings'), ee('CP/URL')->make('addons/settings/data_import/database_settings'));
        $this->data_import_usage = $this->sidebar->addHeader(lang('data_import_usage'), ee('CP/URL')->make('addons/settings/data_import/usage'));

		ee()->load->helper(array('html'));
		ee()->load->library(array('db_lib', 'table', 'data_import_config'));
		ee()->load->model(array('data_import_model', 'data_import_list_model', 'data_import_remote_model'));
		// default global view variables
		$vars = array(
			'cp_data_import_table_template' => array(
				'table_open'		=> '<table class="mainTable data_import_table" border="0" cellspacing="0" cellpadding="0">',
				'row_start'			=> '<tr class="even">',
				'row_alt_start'		=> '<tr class="odd">'
		),
			'action_url' => $this->_action_url,
			'base_url' => $this->_base_url,
		);
		
		// load css 
		ee()->cp->add_to_head('<link rel="stylesheet" type="text/css" href="'.ee()->config->item('theme_folder_url').'third_party/data_import/css/cp.css" />');	
			
		ee()->cp->load_package_js('cp');	
		ee()->load->vars($vars);			
	}
	
	// ----------------------------------------------------------------

	/**
	 * Index Function
	 *
	 * @return 	void
	 */
	public function index()
	{
		return $this->import_list();	
	}
	
	public function import_list()
	{
		$js = "baseUrl='".htmlspecialchars_decode($this->_base_url)."'";

		ee()->javascript->output($js);		
		$options['order_by'] = 'title';
		$vars['import_list'] = ee()->data_import_list_model->get($options);
		return ee()->load->view('import_list', $vars, TRUE);
	}
	
	public function add_import_item()
	{
		if ( ! empty($_POST))
		{
			$data['title'] = ee()->input->post('title');
			$import_id = ee()->data_import_list_model->save($data);
			
			ee('CP/Alert')->makeInline('shared-form')
				->asSuccess()
				->withTitle(lang('success'))
				->addToBody(lang('import_item_saved'))
				->defer();

			ee()->functions->redirect(ee('CP/URL')->make('addons/settings/data_import/settings/'.$import_id));
		}
	}
		
	public function remove_import_item()
	{
		if (ee()->input->get('import_id'))
		{
			$data['import_id'] 	= ee()->input->get('import_id');
			ee()->data_import_list_model->delete($data);
			$js = "$.ee_notice( '".lang('import_item_deleted')."' , {type: 'success', open:true});";
			$this->_make_js($js);
			exit;
		}
	}		
	public function update_import_item()
	{
		if (ee()->input->get('import_id'))
		{
			$data['title'] 		= ee()->input->get('title');
			$data['import_id'] 	= ee()->input->get('import_id');
			ee()->data_import_list_model->save($data);
			$js = "\$('#{$data['import_id']}').text('{$data['title']}').show();\$('#edit_import_{$data['import_id']}').remove();$.ee_notice( '".lang('import_item_saved')."' , {type: 'success', open:true});";
			$this->_make_js($js);
			exit;
		}
	}
	
	public function settings($import_id='')
	{
		ee()->view->cp_page_title = lang('data_import_settings');
		// check if database connection data is correct
		$vars = ee()->data_import_config->items();
		$vars['import_id'] = $import_id ? $import_id : ee()->input->get_post('import_id');
		$vars = array_merge($vars, ee()->data_import_list_model->get_settings($vars['import_id']));
		
		if( ! ee()->data_import_remote_model->connect($vars))
		{
			ee('CP/Alert')->makeInline('shared-form')
				->asIssue()
				->withTitle(lang('error'))
				->addToBody(lang('invalid_database_settings'))
				->defer();
			
			ee()->functions->redirect(ee('CP/URL')->make('addons/settings/data_import/database_settings'));
		}
		// check for submitted general form
		if ( ! empty($_POST['sbt']))
		{
			$upd['import_id'] = ee()->input->post('import_id');
			$upd['settings'] = serialize($_POST);
			ee()->data_import_list_model->save($upd);

			ee('CP/Alert')->makeInline('shared-form')
				->asSuccess()
				->withTitle(lang('success'))
				->addToBody(lang('preferences_updated'))
				->defer();

			ee()->functions->redirect(ee('CP/URL')->make('addons/settings/data_import/settings/'.$upd['import_id']));			
		}

		$tables = ee()->data_import_remote_model->get_tables();
		$remote_tables = array(''=>lang('select_table'));
		if (is_array($tables)) {
			$remote_tables = array_merge($remote_tables, $tables);
		}
		$channels = ee()->data_import_model->get_channels();
		$member_groups = ee()->data_import_model->get_member_groups();
		$remote_table_keys = array(''=>lang('select_key'));


		if($vars['remote_table'])
		{
			$keys_ext = ee()->data_import_remote_model->get_table_keys_ext($vars['remote_table']);
			if (is_array($keys_ext)) {
				$remote_table_keys = array_merge($remote_table_keys, $keys_ext);
			}
		}

		$channel_fields = array();
		if(ee()->input->get('channel'))
		{
			$channel_fields = ee()->data_import_model->get_channel_fields(ee()->input->get('channel'));
			$vars['statuses'] = $this->_get_channel_status_groups(ee()->input->get('channel'));
			$vars['channel_category_groups'] = ee()->data_import_model->get_channel_category_groups(ee()->input->get('channel'));
		}
		elseif ($vars['channel'])
		{
			$vars['statuses'] = $this->_get_channel_status_groups($vars['channel']);
			$channel_fields = ee()->data_import_model->get_channel_fields($vars['channel']);
			$vars['channel_category_groups'] = ee()->data_import_model->get_channel_category_groups($vars['channel']);
		}
		
		$vars['channels'] 			= $channels;
		$vars['channel_fields'] 	= $channel_fields;
		$vars['all_fields'] 		= ee()->data_import_model->fields;
		$vars['remote_tables'] 		= $remote_tables;
		$vars['remote_table_keys']	= $remote_table_keys;
		$vars['member_groups']		= $member_groups;
		$vars['view_assign_fields'] = '';

		if($table = ee()->input->get('table'))
		{
			$table_keys = array();
			$keys = ee()->data_import_model->get_table_keys($table);
			if (is_array($keys)) {
				$table_keys = array_merge($table_keys, $keys);
			}
			$this->_make_table_keys($table_keys, 'key_field');
			
			exit;
		}
		
		if(ee()->input->get('getcondition'))
		{
			extract($_GET);
			$tables = array($table1,$table2);
			$cond = $this->_join_condition($tables);
			$this->_make_js( "\$('#cond{$table1}').remove();");
			$js = "";
			foreach ($cond as $table => $c) 
			{

				$input = addslashes("<span style='width:30%' id='cond{$table1}'>".lang('ON').nbs().form_input("condition[{$table1}_{$table2}]", @$c['cond'], 'class="condition" style="width:60%"'))."</span>";

				$js .= '$(".'.$table1.' select").after("'.$input.'");';
				
					
				break;			
			}
			$this->_make_js($js);
		}
		if(ee()->input->get('changefields') or ee()->input->get('channel'))
		{
			extract($_POST);
			$remote_table_val = isset($remote_table) ? $remote_table : null;
			if ($remote_table_val) {
				$keys_ext = ee()->data_import_remote_model->get_table_keys_ext($remote_table_val);
				if (is_array($keys_ext)) {
					$vars['remote_table_keys'] = $remote_table_keys = array_merge($remote_table_keys, $keys_ext);
				}
			}
			$this->_make_table_keys($remote_table_keys, 'remote_key_field');

			if($channel_fields)
			{
				$js = '$("#assign_fields").html("'.addslashes($vars['view_assign_fields'] = ee()->load->view('assign_fields', $vars, TRUE)).'")';
				$js = str_replace("\r", '', $js);
				$js = str_replace("\n", '', $js);
				$this->_make_js($js);
			}
			exit;
		}


		if($vars['channel'])
		{
			$vars['view_assign_fields'] = ee()->load->view('assign_fields', $vars, TRUE);
		}

		ee()->javascript->compile();
		
		$view = ee()->load->view('assign_settings', $vars, TRUE);

		return $view;
	}	
	
	private function _get_channel_status_groups($channel_id)
	{
		$statuses_sel = array();
		
		// Use Model Service to get channel and its statuses
		$channel = ee('Model')->get('Channel', $channel_id)->first();
		
		if (!$channel) {
			return $statuses_sel;
		}
		
		// Get statuses directly from channel (Channel has direct Statuses relationship in EE7)
		$statuses = $channel->Statuses;
		
		if (!$statuses || $statuses->count() == 0) {
			return $statuses_sel;
		}
		
		foreach ($statuses as $status) 
		{
			$statuses_sel[$status->status] = ucfirst($status->status);
		}
		
		return $statuses_sel;
	}
	
	private function _make_table_keys($table_keys, $select_name)
	{
		$js = "<script>";
		$js .= '$("select[name='.$select_name.'] option").remove();';
		foreach ($table_keys as $key)
		{
			$js .= '$("select[name='.$select_name.']").append($("<option></option>").attr("value","'.$key.'").text("'.$key.'"));';
		}
		$js .= "</script>";
		echo $js;
	}
	
	private function _make_js($cont)
	{
		$js = "<script>";
		$js .= $cont;
		$js .= "</script>\n";
		echo $js;
	}
		
	public function ftp_settings()
	{
		ee()->view->cp_page_title = lang('ftp_settings');

		// check for submitted general form
		if ( ! empty($_POST))
		{
			$this->_save_settings($this->_base_url.AMP.'method=ftp_settings');
		}

		$vars = ee()->data_import_config->items();
		return  ee()->load->view('ftp_settings', $vars, TRUE);
	}
		
	public function database_settings()
	{
		ee()->view->cp_page_title = lang('database_settings');

		// check for submitted general form
		if ( ! empty($_POST))
		{
			$this->_save_settings($this->_base_url.'/database_settings');
		}

		$vars = ee()->data_import_config->items();
		return  ee()->load->view('database_settings', $vars, TRUE);
	}
		
	public function usage()
	{
		ee()->view->cp_page_title =  lang('Help');

		return  ee()->load->view('help', '', TRUE);
	}	
	
	private function _save_settings($redirect)
	{
		ee()->data_import_config->items($_POST);
		ee()->data_import_config->save();

		ee('CP/Alert')->makeInline('shared-form')
			->asSuccess()
			->withTitle(lang('success'))
			->addToBody(lang('preferences_updated'))
			->defer();

		ee()->functions->redirect(ee('CP/URL')->make($redirect));
	}
	
	private function _join_condition($tables)
	{
		foreach ($tables as $table)
		{
			$sql = "SHOW KEYS FROM $table WHERE Key_name = 'PRIMARY'";
			$query = ee()->data_import_remote_model->rdb->query($sql);
			$row_array = $query->row_array();
			if (isset($row_array['Column_name'])) $primary[$table] = $row_array['Column_name'];

			$table_fields[$table] = ee()->data_import_remote_model->get_table_keys($table);
		}
		
		$passed_checks = array();
		foreach ($tables as $k => $table)
		{
			$tmp = $tables;
			unset($tmp[$k]);
			$cond[$table] = "";
			foreach ($tmp as $table1)
			{
				
				if(isset($table_fields[$table1][@$primary[$table]]) and ! isset($passed_checks[$table.$table1]))
				{
					$passed_checks[$table1.$table] = $table; // avoid reverse
					$passed_checks2[] = $table; // avoid reverse
					$cond[$table]['cond'] = "{$table}.{$primary[$table]}={$table1}.{$table_fields[$table1][$primary[$table]]}";
					$cond[$table]['table'] = $table1;
					$cond[$table]['type'] = 'left';
				} 
				else 
				{
					foreach ($table_fields[$table1] as $field)
					{
						if(isset($table_fields[$table][$field]))
						{
							$passed_checks[$table1.$table] = $table; // avoid reverse
							$passed_checks2[] = $table; // avoid reverse
							$cond[$table]['cond'] = "{$table}.{$field}={$table1}.{$field}";
							$cond[$table]['table'] = $table1;
						}
					}
				}
			}
			
		}
		
		foreach ($passed_checks as $k => $table)
		{
			$ok = false;
			foreach ($tables as $k1 => $table1)
			{
				if(strstr($k,$table1)) $ok = true;
			}
			if($ok and !$cond[$table1])
			unset($cond[$table1]);
		}
		return $cond;
	}
	
	public function do_import()
	{
		ee()->load->library(array('data_import_process'));
		ee()->data_import_process->start(ee()->input->get('import'));
		
		ee('CP/Alert')->makeInline('shared-form')
			->asSuccess()
			->withTitle(lang('success'))
			->addToBody(lang('Done'))
			->defer();

		ee()->functions->redirect(ee('CP/URL')->make('addons/settings/data_import'));
	}
}

if( ! function_exists("myd"))
{
function myd($arr,$exit=false){
	if (isset($GLOBALS['debugifon']) and !isset($_REQUEST['debug'])) {
		return ;
	} 
	
	if ($exit === 2) 
	ob_start();
	if (is_array($arr)) {
		echo "<pre>";
		print_r($arr);
		echo "</pre>";
	} elseif (is_string($arr)) {
		echo $arr."<br>";
	} elseif (is_object($arr)) {
		echo "<pre>";
		var_export($arr)."<br>";
		echo "</pre>";
	} else {
		echo ($arr)."<br>";
	}

	if ($exit === 2) {
		$cont = ob_get_contents();
		ob_end_clean();
		file_put_contents('myd2.debug.txt', str_replace("<br>", "\n", $cont), FILE_APPEND );
	}
	if ($exit === 1) exit;
}
}
/* End of file mcp.data_import.php */
/* Location: /system/expressionengine/third_party/data_import/mcp.data_import.php */