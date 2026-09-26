<?php if($channel_fields) { ?>
<div class="box">
	<h3><?= lang('params') ?></h3>
	
	<div class="form-group">
		<label><?= lang('condition') ?></label>
		<div>
			<select name="import_if" class="form-control" style="display:inline-block; width:40%">
				<?php foreach($remote_table_keys as $key => $val): ?>
					<option value="<?= ee('Format')->make('Text', $key)->convertToEntities() ?>" <?= ($import_if == $key) ? 'selected' : '' ?>><?= ee('Format')->make('Text', $val)->convertToEntities() ?></option>
				<?php endforeach; ?>
			</select>
			&nbsp;=&nbsp;
			<input type="text" name="condition_equal_value" value="<?= ee('Format')->make('Text', $condition_equal_value)->convertToEntities() ?>" style="width:40%" class="form-control" style="display:inline-block;">
		</div>
	</div>
	
	<div class="form-group">
		<label><?= lang('create_if_not_exists') ?></label>
		<div>
			<label><input type="radio" name="create_if_not_exists" value="Y" <?= ($create_if_not_exists == 'Y') ? 'checked' : '' ?>> <?= lang('yes') ?></label>
			&nbsp;
			<label><input type="radio" name="create_if_not_exists" value="N" <?= ($create_if_not_exists == 'N') ? 'checked' : '' ?>> <?= lang('no') ?></label>
		</div>
	</div>
	
	<div class="form-group">
		<label><?= lang('delete_if_not_exists') ?></label>
		<div>
			<label><input type="radio" name="delete_if_not_exists" value="Y" <?= (isset($delete_if_not_exists) && $delete_if_not_exists == 'Y') ? 'checked' : '' ?>> <?= lang('yes') ?></label>
			&nbsp;
			<label><input type="radio" name="delete_if_not_exists" value="N" <?= (isset($delete_if_not_exists) && $delete_if_not_exists == 'N') ? 'checked' : '' ?>> <?= lang('no') ?></label>
		</div>
	</div>
</div>
<?php

$this->table->clear();
$this->table->set_template($cp_table_template);
$this->table->set_heading(
		array('data' => lang('entry_info'), 'width' => "30%"),
		array('data' => ''));
		
		// Initialize variables if not set
		if (!isset($assigned_fields)) {
			$assigned_fields = array();
		}
		if (!isset($required)) {
			$required = array();
		}
		if (!isset($match_key)) {
			$match_key = '';
		}
		if (!isset($remote_field)) {
			$remote_field = array();
		}
		
		$assigned_title = (is_array($assigned_fields) && isset($assigned_fields['title'])) ? $assigned_fields['title'] : '';
		$assigned_entry_date = (is_array($assigned_fields) && isset($assigned_fields['entry_date'])) ? $assigned_fields['entry_date'] : '';
		$assigned_expiration_date = (is_array($assigned_fields) && isset($assigned_fields['expiration_date'])) ? $assigned_fields['expiration_date'] : '';
		$assigned_status = (is_array($assigned_fields) && isset($assigned_fields['status'])) ? $assigned_fields['status'] : '';
		
		$this->table->add_row(
	        $this->lang->line('new_entry_title'), 
		    form_dropdown("assigned_fields[title]", $remote_table_keys, $assigned_title).' &nbsp;OR&nbsp; '.form_input('new_entry_title', $new_entry_title,'style="width:40%"')
		);
		
		$this->table->add_row(
		$this->lang->line('entry_date_start'), 
		form_dropdown("assigned_fields[entry_date]", $remote_table_keys, $assigned_entry_date)
		);
		
		$this->table->add_row(
		$this->lang->line('entry_date_finish'), 
		form_dropdown("assigned_fields[expiration_date]", $remote_table_keys, $assigned_expiration_date)
		);

		$statuses["same"] = "Same";
		
		$this->table->add_row(
		$this->lang->line('entry_status'), 
		form_dropdown("assigned_fields[status]", $statuses, $assigned_status)
		);

		/*$statuses["same"] = "Same";

		$this->table->add_row("<label for='condition'>Status for Updated Entries</label><br>Select either open or closed or same as current", 
		form_dropdown("assigned_fields[status_update]", $statuses, @$assigned_fields['status_update'])
		);*/

		$this->table->add_row(
		$this->lang->line('author'), 
		form_dropdown("author_id", $this->db_lib->format_members('username', array('order_by'=>'username')), @$author_id)
		);

		$this->table->add_row(
		$this->lang->line('site'), 
		form_dropdown("site_id", $this->db_lib->format_sites('site_label', array('order_by'=>'site_label')), @$site_id)
		);

		$this->table->add_row(
		$this->lang->line('member_group'), 
		form_dropdown("member_group_id", $member_groups, @$member_group_id)
		);
		echo $this->table->generate();
?>

<?php
$remote_table_keys_short = array();
foreach ($remote_table_keys as $k => $v) 
{
	if( ! strchr($k, '.'))
	{
		$remote_table_keys_short[$k] = $v;
		continue;
	}
	$p = explode('.', $k);
	$remote_table_keys_short[$p[1]] = $v;
}
echo "<h3>".lang('categories')."</h3>";
$this->table->clear();
$cat_template = $cp_table_template;
$cat_template['table_open']	= '<table class="mainTable data_import_table di_category" border="0" cellspacing="0" cellpadding="0">';
$this->table->set_template($cat_template);
$this->table->set_heading(
		array('data' => lang('groups')),
		array('data' => lang('remote_table_field'))
		);
		foreach ($channel_category_groups as $group_id => $group_name) 
		{
			$remote_field_val = (is_array($remote_field) && isset($remote_field[$group_id])) ? $remote_field[$group_id] : '';
			$this->table->add_row(
			    $group_name,
			    form_dropdown("remote_field[$group_id]", $remote_table_keys_short, $remote_field_val).$this->lang->line('categories_help')
			);
		}

		echo $this->table->generate();
?>
<?php
	$this->table->clear();
	$this->table->set_template($cp_table_template);
	$this->table->set_heading(
		array('data' => lang('field_label')),
		array('data' => lang('match_key'), 'width'=>'5%'),
		array('data' => lang('required'), 'width'=>'5%'),
		array('data' => lang('remote_table_field')));

	foreach ($channel_fields as $key => $field) 
	{
		
		echo form_hidden("required[$key]", 'N');
		if(is_array($field))
		{
			$field_id = key($field);
			$fields = current($field);
			$table = form_hidden("assigned_fields[{$key}][{$field_id}]", $field_id);

			$table .= "<table border=0 class='mainTable' style='width:40%'>
			<thead>
				<tr>
					<th>".lang('field_label')."</th>
					<th>".lang('match_key')."</th>
					<th>".lang('required')."</th>
					<th>".lang('remote_table_field')."</th>
				</tr>
			</thead>";
			foreach ($fields as $key1 => $field1)
			{
				if( $key1 == 'sale_price' || $key1 == 'sale_price_enabled' ) continue;

				$match_key_val = (is_array($match_key) && isset($match_key[$field_id])) ? $match_key[$field_id] : '';
				$required_val = (is_array($required) && isset($required[$key]) && is_array($required[$key]) && isset($required[$key][$key1]) && $required[$key][$key1] == 'Y') ? true : false;
				$assigned_val = (is_array($assigned_fields) && isset($assigned_fields[$key]) && is_array($assigned_fields[$key]) && isset($assigned_fields[$key][$field_id]) && is_array($assigned_fields[$key][$field_id]) && isset($assigned_fields[$key][$field_id][$key1])) ? $assigned_fields[$key][$field_id][$key1] : '';

				$table .= "<tr><td>{$field1}</td><td>".form_radio("match_key[{$field_id}]", $key1,  ($match_key_val == $key1))."</td><td>". form_checkbox("required[$key][$key1]", 'Y',  $required_val) ."</td><td>".form_dropdown("assigned_fields[{$key}][{$field_id}][{$key1}]", $remote_table_keys, $assigned_val)."</td></tr>";
			}
			$table .= "</table>";
			$field_label = (is_array($all_fields) && isset($all_fields[$field_id]) && is_array($all_fields[$field_id]) && isset($all_fields[$field_id]['field_label'])) ? $all_fields[$field_id]['field_label'] : '';
			$this->table->add_row(
		        $field_label,
		        '', 
		        '', 
		        $table
		    );
		    continue;			
		}
		
		$match_key_checked = (is_string($match_key) && $match_key == $key) || (is_array($match_key) && isset($match_key[$key]) && $match_key[$key] == $key);
		$required_checked = (is_array($required) && isset($required[$key]) && $required[$key] == 'Y');
		$assigned_val = (is_array($assigned_fields) && isset($assigned_fields[$key])) ? $assigned_fields[$key] : '';
		
		$this->table->add_row(
	        $field, 
	        form_radio("match_key", $key,  $match_key_checked), 
	        form_checkbox("required[$key]", 'Y',  $required_checked), 
	        form_dropdown("assigned_fields[{$key}]", $remote_table_keys, $assigned_val)
	    );		
	}
   
	echo $this->table->generate();
}
?>