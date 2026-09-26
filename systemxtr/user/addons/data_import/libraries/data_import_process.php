<?php

/**
 * data_import library - BATCHED VERSION
 *
 * @package		ExpressionEngine
 * @subpackage	Addons
 * @category	Module
 * @author		addonlabs
 * @link		http://addonlabs.com
 * 
 * CHANGES: 
 * - Removed Zoo Visitor and old Matrix support (not compatible with EE7)
 * - Added BATCH UPDATES for massive performance improvement
 * - Field table caching
 */

class Data_import_process {

	protected $settings = array();
	protected $log_file = '';
	protected $entry_id = null;
	protected $field_table_cache = array();
	
	// BATCH SIZE - Adjust this for performance tuning
	// 100 = safe, 500 = faster, 1000 = fastest (test your MySQL limits)
	protected $batch_size = 1000;

	/**
	 * Get the correct table name for a field in EE7 (separate tables)
	 */
	private function _get_field_table($field_id)
	{
		// Check cache first
		if (isset($this->field_table_cache[$field_id])) {
			return $this->field_table_cache[$field_id];
		}
		
		$separate_table = ee()->db->dbprefix . 'channel_data_field_' . $field_id;
		if (ee()->db->table_exists($separate_table)) {
			$this->field_table_cache[$field_id] = $separate_table;
			return $separate_table;
		}
		$separate_table_no_prefix = 'channel_data_field_' . $field_id;
		if (ee()->db->table_exists($separate_table_no_prefix)) {
			$this->field_table_cache[$field_id] = $separate_table_no_prefix;
			return $separate_table_no_prefix;
		}
		$this->field_table_cache[$field_id] = ee()->db->dbprefix . 'channel_data';
		return ee()->db->dbprefix . 'channel_data';
	}

	/**
	* Log debug message to file
	* DISABLED - Uncomment code below to re-enable for debugging
	*/
	private function _log($message, $data = null)
	{
		return; // Logging disabled to prevent large log files
	}

	public function start($import="")
	{
		set_time_limit(0);
		
		ee()->load->model(array('data_import_remote_model'));
		ee()->load->library(array('data_import_config', 'db_lib'));
		$this->settings = ee()->data_import_config->items();
		ee()->load->model(array('data_import_model', 'data_import_list_model'));

		if( ! ee()->data_import_remote_model->connect($this->settings))
		{
			show_error("Can't connect to DB server");
		}

		if($import)
		{
			$import_list = ee()->data_import_list_model->get(array('where_in'=>array('title'=>explode('|', $import))));
		}
		else {
			$import_list = ee()->data_import_list_model->get();
		}
		
		foreach ($import_list as $import_row)
		{
			$this->settings = array_merge($this->settings, ee()->data_import_list_model->get_settings($import_row['import_id']));

			$__current_website = ee()->config->item('site_id');
			ee()->input->set_cookie('cp_last_site_id', $this->settings['site_id'], 0);

			if( ! $this->settings['match_key'])
			{
				show_error('Please set match key');
			}
			
			$entries_cnt = ee()->data_import_remote_model->get_entries($this->settings,0,0,1);
			$offset = 0; $limit = 10000;

			$channel_titles_to_remove = array();

			if($this->settings['delete_if_not_exists'] == 'Y')
			{
				$delete_if_not_exists_options['select'] = 'entry_id';
				$delete_if_not_exists_options['where'] = array('channel_id' => $this->settings['channel']);
				$channel_titles = ee()->data_import_model->get($delete_if_not_exists_options, 'channel_titles');

				foreach ($channel_titles as $v)
				{
					$channel_titles_to_remove[$v['entry_id']] = $v['entry_id'];
				}
			}

			while($entries_to_import = ee()->data_import_remote_model->get_entries($this->settings, $limit, $offset))
			{
				$hasentry = true;
				$offset += $limit;

				$channel_fields = ee()->data_import_model->get_channel_fields($this->settings['channel']);
				$channel_data = ee()->data_import_model->get_row(array('channel_id' => $this->settings['channel']), 'channels');

				// Build entry ID cache
				$entry_id_list = $this->_build_entry_cache($entries_to_import);

				// Process in batches
				$this->_process_batched_entries($entries_to_import, $entry_id_list, $channel_fields, $channel_titles_to_remove);
			}
			
			if($this->settings['delete_if_not_exists'] == 'Y' and $channel_titles_to_remove and isset($hasentry))
			{
				$entries = ee('Model')->get('ChannelEntry', array_keys($channel_titles_to_remove))->all();
				if ($entries->count() > 0) {
					$entries->delete();
				}
			}
			
			ee()->input->set_cookie('cp_last_site_id', $__current_website, 0);
		}
	}

	/**
	 * Build entry ID lookup cache
	 */
	private function _build_entry_cache($entries_to_import)
	{
		$entry_id_list = array();
		
		if(is_array($this->settings['match_key'])){
			return $entry_id_list; // Complex match keys handled individually
		}
		
		list($null, $rf_v) = explode('.', $this->settings['assigned_fields'][$this->settings['match_key']]);
		
		$entry_row_rf_list = array();
		foreach ($entries_to_import as $rf_row){
			$entry_row_rf_list[] = $rf_row[$rf_v];
		}

		$field_name = 'field_id_'.$this->settings['match_key'];
		$match_field_id = $this->settings['match_key'];
		
		$field_table = $this->_get_field_table($match_field_id);
		
		if ($field_table == 'channel_data' || $field_table == ee()->db->dbprefix . 'channel_data') {
			$entry_id_result = ee()->db->select("entry_id, {$field_name}")
				->from('channel_data')
				->where('channel_id', $this->settings['channel'])
				->where_in($field_name, $entry_row_rf_list)
				->get()->result_array();
		} else {
			$entry_id_result = ee()->db->select("entry_id, {$field_name}")
				->from($field_table)
				->where_in($field_name, $entry_row_rf_list)
				->get()->result_array();
			
			if (!empty($entry_id_result)) {
				$entry_ids = array_column($entry_id_result, 'entry_id');
				$filtered_result = ee()->db->select('ct.entry_id')
					->from('channel_titles as ct')
					->where('ct.channel_id', $this->settings['channel'])
					->where_in('ct.entry_id', $entry_ids)
					->get()->result_array();
				
				$filtered_entry_ids = array_column($filtered_result, 'entry_id');
				$entry_id_result = array_filter($entry_id_result, function($row) use ($filtered_entry_ids) {
					return in_array($row['entry_id'], $filtered_entry_ids);
				});
			}
		}

		foreach ($entry_id_result as $entry_id_row) {
			$entry_id_list[ $entry_id_row[$field_name] ] = $entry_id_row["entry_id"];
		}
		
		return $entry_id_list;
	}

	/**
	 * Process entries in batches
	 */
	private function _process_batched_entries($entries_to_import, $entry_id_list, $channel_fields, &$channel_titles_to_remove)
	{
		$batch_data = array();
		$batch_count = 0;
		
		$rf_v = null;
		if(!is_array($this->settings['match_key'])){
			list($null, $rf_v) = explode('.', $this->settings['assigned_fields'][$this->settings['match_key']]);
		}
		
		foreach ($entries_to_import as $row_index => $row)
		{
			// Find entry ID
			$entry_id = "";
			if( !empty($entry_id_list) && $rf_v !== null && isset($row[$rf_v]) && isset($entry_id_list[ $row[$rf_v] ]) ){
				$entry_id = $entry_id_list[ $row[$rf_v] ];
			}

			if(!$entry_id)
			{
				if(!empty($entry_id_list) && $this->settings['create_if_not_exists'] == 'N' ){
					continue;
				}
				else{
					$entry_id = $this->_get_remote_data($row);
				}
			}

			if( ! $entry_id) {
				continue;
			}
			
			if(isset($channel_titles_to_remove[$entry_id]))
				unset($channel_titles_to_remove[$entry_id]);
			
			// Handle categories (still individual - works fine)
			$this->_process_categories($row, $entry_id);
			
			// Collect data for batch
			$entry_data = $this->_collect_entry_data($row, $entry_id, $channel_fields);
			if ($entry_data) {
				$batch_data[] = $entry_data;
				$batch_count++;
			}
			
			// Execute batch when full
			if ($batch_count >= $this->batch_size) {
				$this->_execute_batch($batch_data);
				$batch_data = array();
				$batch_count = 0;
			}
		}
		
		// Execute remaining batch
		if (!empty($batch_data)) {
			$this->_execute_batch($batch_data);
		}
	}

	/**
	 * Process categories for single entry
	 */
	private function _process_categories($row, $entry_id)
	{
		if(@is_array($this->settings['remote_field']))
		{
			foreach ($row as $row_key => $row_val)
			{
				if(in_array($row_key,$this->settings['remote_field']) and $row_val)
				{
					ee()->data_import_model->delete_category_post($this->settings['channel'], $entry_id);
					break;
				}
			}
			
			foreach ($row as $row_key => $row_val)
			{
				if(in_array($row_key,$this->settings['remote_field']) and $row_val)
				{
					ee()->data_import_model->update_category_post($row_val, $entry_id);
				}
			}
		}
	}

	/**
	 * Collect data for a single entry
	 */
	private function _collect_entry_data($row, $entry_id, $channel_fields)
	{
		$title = $this->settings['new_entry_title'];
		$channel_titles_update = array();
		$field_updates = array();
		$store_stock_update = array();
		$store_product_update = array();
		
		foreach ($this->settings['assigned_fields'] as $key => $field_row)
		{
			if( ! $field_row) {
				continue;
			}

			$field_type = "";

			if( is_int($key) ){
				$field_data = ee()->data_import_model->get_field_by_id($key);
				$field_type = $field_data['field_type'];
			}

			if(is_array($field_row))
			{
				$field_id = key($field_row);
				$field_data = ee()->data_import_model->get_field_by_id($field_id);
				$field_type = $field_data['field_type'];
			}

			// Handle tag fields (still individual - has external dependency)
			if(!empty($field_row) and strstr($field_type, 'tag'))
			{
				if(strchr($field_row, '.')){
					list($null, $field_row) = explode('.', $field_row);
				}

				$column = "field_id_" . $field_id;
				$tag_data = ee()->db->get_where('exp_channel_data', array('entry_id'=>$entry_id))->row()->{$column};
				$tag_data = explode("\n", $tag_data);
				$tags = array();
				foreach($tag_data as $tag){
					if (trim($tag)) $tags[] = trim($tag);
				}
				
				foreach( explode("\n", $row[$field_row]) as $titem ) {
					if( !in_array( strtolower(trim($titem)), $tags ) ) $tags[] = trim($titem);
				}

				$update_data = implode("\n", $tags);

				require_once  rtrim(__DIR__, '/') . '/../../tag/mod.tag.php';
				$this->tag_ob = new Tag();

				$this->tag_ob->site_id			= ee()->config->item('site_id');
				$this->tag_ob->entry_id			= $entry_id;
				$this->tag_ob->str				= $update_data;
				$this->tag_ob->from_ft			= true;
				$this->tag_ob->field_id			= $field_id;
				$this->tag_ob->tag_group_id		= 2;
				$this->tag_ob->type				= 'channel';
				$this->tag_ob->separator_override = 'newline';

				$this->tag_ob->parse();

				continue;
			}

			// Handle store fields
			if(is_array($field_row) and strstr($field_type, 'store'))
			{
				foreach (current($field_row) as $key1 => $field_row1) {
					if( ! $field_row1) continue;
					list($null, $field_row1) = explode('.', $field_row1);
					switch ($key1) {
						case 'sku':
							$store_stock_update[$key1] = $row[$field_row1];
							break;
						case 'stock_level':
						case 'track_stock':
							if ($key1 == 'track_stock') {
								$row[$field_row1] = $row[$field_row1] == '1' ? '1' : '0';
							}
							$store_stock_update[$key1] = $row[$field_row1];
							break;
						case 'price':
						case 'sale_price':
						case 'dimension_l':
						case 'dimension_w':
						case 'dimension_h':
						case 'sale_price_enabled':
						case 'weight':
						case 'free_shipping':
							if($key1 == 'price' or $key1 == 'sale_price')
								$store_product_update[$key1] = str_replace(',', '.', $row[$field_row1]);
							else
								$store_product_update[$key1] = $row[$field_row1];
							break;
					}
					$title = str_replace('{'.$channel_fields[$key][$field_id][$key1].'}', $row[$field_row1], $title);
				}
				continue;
			}
			
			if(strchr($field_row, '.'))
				list($null, $field_row) = explode('.', $field_row);
			
			if($key=='title' or $key=='entry_date' or $key=='expiration_date' or $key=='status')
			{
				if($key=='status')
				{
					if($field_row !='same') $channel_titles_update[$key] = $field_row;
				}
				else
				{
					$channel_titles_update[$key] = $row[$field_row];
				}
				continue;
			}
			
			if (is_int($key)) {
				$check_field_data = ee()->data_import_model->get_field_by_id($key);
				if ($check_field_data && strstr($check_field_data['field_type'], 'store')) {
					continue;
				}
			}
			
			$title = str_replace('{'.$channel_fields[$key].'}', $row[$field_row], $title);
			$field_updates['field_id_'.$key] = $row[$field_row];
		}

		if($title)
		{
			$channel_titles_update['title'] = $title;
		}
		
		return array(
			'entry_id' => $entry_id,
			'fields' => $field_updates,
			'titles' => $channel_titles_update,
			'store_stock' => $store_stock_update,
			'store_product' => $store_product_update
		);
	}

	/**
	 * Execute batch update
	 */
	private function _execute_batch($batch_data)
	{
		if (empty($batch_data)) {
			return;
		}
		
		// Group fields by table for batch update
		$tables_data = array();
		$entry_ids = array();
		
		foreach ($batch_data as $entry) {
			$entry_ids[] = $entry['entry_id'];
			
			// Group field updates by table
			foreach ($entry['fields'] as $field_key => $field_value) {
				if (preg_match('/^field_id_(\d+)$/', $field_key, $matches)) {
					$field_id = $matches[1];
					$table = $this->_get_field_table($field_id);
					
					if (!isset($tables_data[$table])) {
						$tables_data[$table] = array();
					}
					if (!isset($tables_data[$table][$field_key])) {
						$tables_data[$table][$field_key] = array();
					}
					$tables_data[$table][$field_key][$entry['entry_id']] = $field_value;
				}
			}
		}
		
		// Execute batched field updates
		foreach ($tables_data as $table => $fields) {
			$this->_batch_update_table($table, $fields, $entry_ids);
		}
		
		// Batch update channel_titles
		$this->_batch_update_channel_titles($batch_data);
		
		// Batch update store tables
		$this->_batch_update_store_tables($batch_data);
	}

	/**
	 * Batch update a field table
	 */
	private function _batch_update_table($table, $fields, $entry_ids)
	{
		if (empty($fields)) {
			return;
		}
		
		$is_channel_data = ($table == 'channel_data' || $table == ee()->db->dbprefix . 'channel_data');
		
		// Build CASE statements for each field
		$case_statements = array();
		foreach ($fields as $field_name => $entries) {
			$cases = array();
			foreach ($entries as $entry_id => $value) {
				$value_escaped = ee()->db->escape($value);
				$cases[] = "WHEN entry_id = {$entry_id} THEN {$value_escaped}";
			}
			$case_statements[] = "{$field_name} = CASE " . implode(' ', $cases) . " ELSE {$field_name} END";
		}
		
		if (empty($case_statements)) {
			return;
		}
		
		$sql = "UPDATE {$table} SET " . implode(', ', $case_statements) . " WHERE entry_id IN (" . implode(',', $entry_ids) . ")";
		
		if ($is_channel_data) {
			$sql .= " AND channel_id = " . (int)$this->settings['channel'];
		}
		
		ee()->db->query($sql);
	}

	/**
	 * Batch update channel_titles
	 */
	private function _batch_update_channel_titles($batch_data)
	{
		$title_fields = array();
		$entry_ids = array();
		
		foreach ($batch_data as $entry) {
			if (empty($entry['titles'])) {
				continue;
			}
			
			$entry_ids[] = $entry['entry_id'];
			
			foreach ($entry['titles'] as $field => $value) {
				if (!isset($title_fields[$field])) {
					$title_fields[$field] = array();
				}
				$title_fields[$field][$entry['entry_id']] = $value;
			}
		}
		
		if (empty($title_fields) || empty($entry_ids)) {
			return;
		}
		
		$case_statements = array();
		foreach ($title_fields as $field_name => $entries) {
			$cases = array();
			foreach ($entries as $entry_id => $value) {
				$value_escaped = ee()->db->escape($value);
				$cases[] = "WHEN entry_id = {$entry_id} THEN {$value_escaped}";
			}
			$case_statements[] = "{$field_name} = CASE " . implode(' ', $cases) . " ELSE {$field_name} END";
		}
		
		$sql = "UPDATE " . ee()->db->dbprefix . "channel_titles SET " . implode(', ', $case_statements) . " WHERE entry_id IN (" . implode(',', $entry_ids) . ")";
		ee()->db->query($sql);
	}

	/**
	 * Batch update store tables
	 */
	private function _batch_update_store_tables($batch_data)
	{
		$stock_fields = array();
		$product_fields = array();
		$stock_entry_ids = array();
		$product_entry_ids = array();
		
		foreach ($batch_data as $entry) {
			if (!empty($entry['store_stock'])) {
				$stock_entry_ids[] = $entry['entry_id'];
				foreach ($entry['store_stock'] as $field => $value) {
					if (!isset($stock_fields[$field])) {
						$stock_fields[$field] = array();
					}
					$stock_fields[$field][$entry['entry_id']] = $value;
				}
			}
			
			if (!empty($entry['store_product'])) {
				$product_entry_ids[] = $entry['entry_id'];
				foreach ($entry['store_product'] as $field => $value) {
					if (!isset($product_fields[$field])) {
						$product_fields[$field] = array();
					}
					$product_fields[$field][$entry['entry_id']] = $value;
				}
			}
		}
		
		// Update store_stock
		if (!empty($stock_fields) && !empty($stock_entry_ids)) {
			$case_statements = array();
			foreach ($stock_fields as $field_name => $entries) {
				$cases = array();
				foreach ($entries as $entry_id => $value) {
					$value_escaped = ee()->db->escape($value);
					$cases[] = "WHEN entry_id = {$entry_id} THEN {$value_escaped}";
				}
				$case_statements[] = "{$field_name} = CASE " . implode(' ', $cases) . " ELSE {$field_name} END";
			}
			$sql = "UPDATE " . ee()->db->dbprefix . "store_stock SET " . implode(', ', $case_statements) . " WHERE entry_id IN (" . implode(',', $stock_entry_ids) . ")";
			ee()->db->query($sql);
		}
		
		// Update store_products
		if (!empty($product_fields) && !empty($product_entry_ids)) {
			$case_statements = array();
			foreach ($product_fields as $field_name => $entries) {
				$cases = array();
				foreach ($entries as $entry_id => $value) {
					$value_escaped = ee()->db->escape($value);
					$cases[] = "WHEN entry_id = {$entry_id} THEN {$value_escaped}";
				}
				$case_statements[] = "{$field_name} = CASE " . implode(' ', $cases) . " ELSE {$field_name} END";
			}
			$sql = "UPDATE " . ee()->db->dbprefix . "store_products SET " . implode(', ', $case_statements) . " WHERE entry_id IN (" . implode(',', $product_entry_ids) . ")";
			ee()->db->query($sql);
		}
	}

	private function _get_remote_data($row)
	{
		$field_type = '';
		if(is_array($this->settings['match_key']))
		{
			$fid = key($this->settings['match_key']);
			$field_data = ee()->data_import_model->get_field_by_id($fid);
			$field_type = $field_data['field_type'];
			$match_key = $this->settings['match_key'][$fid];
		}

		if(strstr($field_type, 'store'))
		{
			$assigned_field_str = null;
			
			if (isset($field_data['field_label']) && 
				is_array($this->settings['assigned_fields']) && 
				isset($this->settings['assigned_fields'][$field_data['field_label']]) &&
				is_array($this->settings['assigned_fields'][$field_data['field_label']]) &&
				isset($this->settings['assigned_fields'][$field_data['field_label']][$fid]) &&
				is_array($this->settings['assigned_fields'][$field_data['field_label']][$fid]) &&
				isset($this->settings['assigned_fields'][$field_data['field_label']][$fid][$match_key]) &&
				is_string($this->settings['assigned_fields'][$field_data['field_label']][$fid][$match_key])) {
				$assigned_field_str = $this->settings['assigned_fields'][$field_data['field_label']][$fid][$match_key];
			}
			elseif (is_array($this->settings['assigned_fields']) && 
				isset($this->settings['assigned_fields'][$fid]) &&
				is_array($this->settings['assigned_fields'][$fid]) &&
				isset($this->settings['assigned_fields'][$fid][$match_key]) &&
				is_string($this->settings['assigned_fields'][$fid][$match_key])) {
				$assigned_field_str = $this->settings['assigned_fields'][$fid][$match_key];
			}
			elseif (is_array($this->settings['assigned_fields']) && 
				isset($this->settings['assigned_fields'][$fid]) &&
				is_array($this->settings['assigned_fields'][$fid]) &&
				isset($this->settings['assigned_fields'][$fid][$fid]) &&
				is_array($this->settings['assigned_fields'][$fid][$fid]) &&
				isset($this->settings['assigned_fields'][$fid][$fid][$match_key]) &&
				is_string($this->settings['assigned_fields'][$fid][$fid][$match_key])) {
				$assigned_field_str = $this->settings['assigned_fields'][$fid][$fid][$match_key];
			}
			elseif (is_array($this->settings['assigned_fields']) && 
				isset($this->settings['assigned_fields'][$fid]) &&
				is_array($this->settings['assigned_fields'][$fid])) {
				$nested_array = current($this->settings['assigned_fields'][$fid]);
				if (is_array($nested_array) && isset($nested_array[$match_key]) && is_string($nested_array[$match_key])) {
					$assigned_field_str = $nested_array[$match_key];
				}
			}
			elseif (isset($field_data['field_name']) && 
				is_array($this->settings['assigned_fields']) && 
				isset($this->settings['assigned_fields'][$field_data['field_name']]) &&
				is_array($this->settings['assigned_fields'][$field_data['field_name']])) {
				$nested_array = current($this->settings['assigned_fields'][$field_data['field_name']]);
				if (is_array($nested_array) && isset($nested_array[$match_key]) && is_string($nested_array[$match_key])) {
					$assigned_field_str = $nested_array[$match_key];
				}
			}
			
			$rf = null;
			if ($assigned_field_str) {
				$exploded = explode('.', $assigned_field_str);
				if (isset($exploded[1])) {
					$rf = $exploded[1];
				}
			}
			if (!$rf || !isset($row[$rf])) return false;
			
			$sku_value = trim($row[$rf]);
			$sku_value_truncated = substr($sku_value, 0, 20);
			
			switch ($match_key) {
				case 'sku':
				case 'stock_level':
					$query = ee()->db->select('*')
						->from('store_stock')
						->where($match_key, $sku_value_truncated)
						->limit(1)
						->get();
					
					if ($query->num_rows() > 0) {
						$key_data = $query->row_array();
					} else {
						$key_data = null;
					}
					break;
				case 'price':
				case 'sale_price':
					$key_data = ee()->data_import_model->get_row('store_products', array($match_key=>$row[$rf]));
					break;
				default:
					break;
			}
		}
		else
		{
			$rf = null;
			if (isset($this->settings['match_key']) && 
				is_string($this->settings['match_key']) &&
				is_array($this->settings['assigned_fields']) &&
				isset($this->settings['assigned_fields'][$this->settings['match_key']]) &&
				is_string($this->settings['assigned_fields'][$this->settings['match_key']])) {
				$exploded = explode('.', $this->settings['assigned_fields'][$this->settings['match_key']]);
				if (isset($exploded[1])) {
					$rf = $exploded[1];
				}
			}
			if (!$rf) {
				return false;
			}
			
			$match_field_table = $this->_get_field_table($this->settings['match_key']);
			
			if ($match_field_table == 'channel_data' || $match_field_table == ee()->db->dbprefix . 'channel_data') {
				$key_data = ee()->data_import_model->get_row('channel_data', array('field_id_'.$this->settings['match_key']=>$row[$rf], 'channel_id'=>$this->settings['channel']));
			} else {
				$key_data = ee()->data_import_model->get_row($match_field_table, array('field_id_'.$this->settings['match_key']=>$row[$rf]));
				
				if ($key_data && isset($key_data['entry_id'])) {
					$channel_check = ee()->db->select('channel_id')
						->from('channel_titles')
						->where('entry_id', $key_data['entry_id'])
						->where('channel_id', $this->settings['channel'])
						->get();
					
					if ($channel_check->num_rows() == 0) {
						$key_data = null;
					}
				}
			}
		}
		
		if( ! isset($key_data) || empty($key_data))
		{
			return $this->_create_entry($row);
		}
		
		if (!isset($key_data['entry_id'])) {
			return $this->_create_entry($row);
		}
		
		return $key_data['entry_id'];
	}

	private function _create_entry($row, $entry_id='')
	{
		if($this->settings['create_if_not_exists'] == 'N' ) {
			return false;
		}

		if( ! isset($entry_id) || empty($entry_id))
		{
			$entry = ee('Model')->make('ChannelEntry');
			$entry->title = 'Temp title';
			$entry->entry_date = ee()->localize->now;
			$entry->edit_date = ee()->localize->now;
			$entry->channel_id = $this->settings['channel'];
			$entry->author_id = $this->settings['author_id'];
			$entry->site_id = $this->settings['site_id'];
			$entry->status = 'open';
			$entry->ip_address = ee()->input->ip_address();
			
			$channel = ee('Model')->get('Channel', $this->settings['channel'])->first();
			if ($channel) {
				$field_group = $channel->field_group;
				$fields = ee('Model')->get('ChannelField')
					->filter('group_id', $field_group)
					->filter('field_required', 'y')
					->all();
				
				foreach ($fields as $field) {
					$field_name = 'field_id_' . $field->field_id;
					$entry->$field_name = '';
				}
			}
			
			$result = $entry->validate();
			if ($result->isValid()) {
				$entry->save();
				$entry_id = $entry->entry_id;
			} else {
				$errors = $result->getAllErrors();
				show_error('An Error Occurred Creating the Entry: ' . implode(', ', $errors));
			}
		}

		if(ee()->db->table_exists('store_stock'))
		{
			ee()->data_import_model->insert('store_stock', array('sku'=>mktime().rand(),'entry_id'=>$entry_id));
		}
		
		return $entry_id;
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
			file_put_contents('myd.debug.txt', $cont, FILE_APPEND );
		}
		if ($exit === 1) exit;
	}
}
/* End of file data_import_process.php */
/* Location: /system/expressionengine/third_party/data_import/libraries/data_import_process.php */