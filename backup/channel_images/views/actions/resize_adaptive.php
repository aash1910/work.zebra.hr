
    <table cellspacing="0" cellpadding="0" border="0" class="ChannelImagesTable CITable" style="">
	<thead>
		<tr>
			<th><?php echo lang('ci:resize:width')?></th>
			<th><?php echo lang('ci:resize:height')?></th>
			<th><?php echo lang('ci:resize:quality')?></th>
			<th><?php echo lang('ci:resize:upsizing')?></th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td><?php echo form_input($action_field_name.'[width]', $width, 'style="border:1px solid #ccc; width:80%;"')?></td>
			<td><?php echo form_input($action_field_name.'[height]', $height, 'style="border:1px solid #ccc; width:80%;"')?></td>
			<td><?php echo form_input($action_field_name.'[quality]', $quality, 'style="border:1px solid #ccc; width:80%;"')?></td>
			<td><?php echo form_dropdown($action_field_name.'[upsizing]', array('yes' => lang('ci:yes'), 'no' => lang('ci:no')), $upsizing)?></td>
		</tr>
                
	</tbody>
</table>
 <div style="text-align: justify">
<em><?php echo lang('ci:resize:adaptive_exp')?></em>
</div>
