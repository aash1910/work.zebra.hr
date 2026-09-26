<?php

class Data_import_model extends Db_lib
{
	var $fields;
	var $channels_data;
	
	public function __construct()
	{
		parent::__construct();
	}
	
	public function get_channels()
	{
		$data = $this->get('','channels');
		$ret  = array("" => lang('select_channel'));
		foreach ($data as $row) 
		{
			$ret[$row['channel_id']] = $row['channel_title'];
		}
		asort($ret);
		return $ret;
	}
	
	public function get_field_by_name($field_name)
	{
		foreach ($this->fields as $k => $v) 
		{
			if($v['field_name'] == $field_name)
				return $v;
		}
	}
	
	public function get_field_by_id($field_id)
	{
		if(isset($this->fields[$field_id]))
			return $this->fields[$field_id];
	}
	
	public function get_channel_fields($channel_id)
	{
		$ret = array();
		
		// Use Model Service to get channel fields through the channel's field groups relationship
		$channel = ee('Model')->get('Channel', $channel_id)->first();
		
		if (!$channel) {
			return $ret;
		}
		
		// Get field groups from channel (many-to-many relationship)
		$field_groups = $channel->FieldGroups;
		
		if (!$field_groups || $field_groups->count() == 0) {
			return $ret;
		}
		
		// Iterate through all field groups and collect their fields
		foreach ($field_groups as $field_group) 
		{
			$fields = $field_group->ChannelFields;
			
			foreach ($fields as $field) 
			{
				$row = array(
					'field_id' => $field->field_id,
					'field_name' => $field->field_name,
					'field_type' => $field->field_type,
					'field_label' => $field->field_label ? $field->field_label : $field->field_name
				);
				
				$this->fields[$field->field_id] = $row;
				
				if($field->field_type == 'store')
				{
					$ret[$field->field_name][$field->field_id] = $this->get_store_fields($field->field_id);
					continue;
				}
				
				$ret[$field->field_id] = $field->field_name;
			}
		}

		asort($ret);
		return $ret;
	}
	
	public function get_upload_prefs_info($name)
	{
		$options['where'] = array('name' => $name);
		$data = $this->get_row($options, 'upload_prefs');
		return $data;
	}
	
	public function get_dir_id($name)
	{
		$data = $this->get_upload_prefs_info($name);
		return $data['id'];
	}
	
	public function get_filedir($name)
	{
		$data = $this->get_upload_prefs_info($name);
		return "{filedir_{$data['id']}}";
	}
	
	public function get_store_fields()
	{
		return array(
			'sku' => 'sku',
			'stock_level' => 'stock_level',
			'track_stock' => 'track_stock',
			'price' => 'price',
			'lenght' => 'lenght',
			'width' => 'width',
			'height' => 'height',
			'handling' => 'handling',
			'free_shipping' => 'free_shipping',
		);
	}

	public function get_member_groups()
	{
		$ret = array();
		// Use Role model for EE6+ (member_groups table was replaced)
		$roles = ee('Model')->get('Role')
			->order('name', 'asc')
			->all();
		
		foreach ($roles as $role) 
		{
			$ret[$role->role_id] = $role->name;
		}
		return $ret;
	}
		
	public function get_table_keys($table)
	{
		$ret = array();
		$fields = $this->db->list_fields($table);
		if (is_array($fields)) {
			sort($fields);
			foreach ($fields as $f) 
			{
				$ret[$f] = $f;
			}
		}
		
		return $ret;
	}
	
	public function get_channel_categories($channel_id)
	{
		$ret = array(''=>lang('select_key'));
		
		$channel_data = $this->get_row(array('channel_id'=>$channel_id), 'channels');
		$groups = explode("|", $channel_data['cat_group']);
		foreach ($groups as $group) 
		{
			$opt['where'] = array('group_id'=>$group);
			$opt['order_by'] = 'cat_name';
			$cats = $this->get($opt, 'categories');
			foreach ($cats as $cat) 
			{
				$ret[$cat['cat_id']] = $cat['cat_name'];
			}
		}
		
		return $ret;
	}
	
	public function get_channel_category_groups($channel_id)
	{
		$ret = array();
		$opt['where'] = array('channel_id'=>$channel_id);
		$channel_data = $this->get_row($opt, 'channels');
		$groups = explode("|", $channel_data['cat_group']);

		foreach ($groups as $group) 
		{
			$opt['where'] = array('group_id'=>$group);
			$opt['order_by'] = 'group_name';
			$cats = $this->get($opt, 'category_groups');
			foreach ($cats as $cat) 
			{
				$ret[$cat['group_id']] = $cat['group_name'];
			}
		}
		
		return $ret;
	}
	
	public function update_category_post($cat_id, $entry_id, $parent=0)
	{
		if( ! $cat_id) return ;

		// for multiple categories ( format must be 66;335;22;19 )
		$categories = explode(";",$cat_id);
		foreach ($categories as $cat_id) {
			$cat_id = trim($cat_id);
						
			$cat_data = $this->get_row(array('cat_id'=>$cat_id), 'categories');
			if($cat_data and $cat_data['parent_id'] > 0)
			{
				$this->update_category_post($cat_data['parent_id'],$entry_id);
			}

			if( ! $c = $this->get_row(array('entry_id'=>$entry_id,'cat_id'=>$cat_id), 'category_posts'))
			{
				$this->insert('category_posts', array('entry_id'=>$entry_id,'cat_id'=>$cat_id));
			}
		}

		return $cat_id;
	}
	
	public function delete_category_post($channel_id, $entry_id)
	{
		// Placeholder - could implement channel-specific category deletion if needed
	}
}

/* End of file data_import_model.php */