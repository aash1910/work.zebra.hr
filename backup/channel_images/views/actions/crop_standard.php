<?php $thstyle='style="border-right-color:rgba(0, 0, 0, 0.1); border-right-style:solid; border-right-width:1px;"';?>
<table cellspacing="0" cellpadding="0" border="0" class="ChannelImagesTable CITable">
	<thead>
		<tr>
			<th><?php echo lang('ci:resize:startx')?></th>
			<th <?php echo $thstyle?>><?php echo lang('ci:resize:starty')?></th>
			<th <?php echo $thstyle?>><?php echo lang('ci:resize:width')?></th>
			<th <?php echo $thstyle?>><?php echo lang('ci:resize:height')?></th>
			<th <?php echo $thstyle?>><?php echo lang('ci:resize:quality')?></th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td><?php echo form_input($action_field_name.'[start_x]', $start_x, 'style="border:1px solid #ccc; width:80%;"')?></td>
			<td><?php echo form_input($action_field_name.'[start_y]', $start_y, 'style="border:1px solid #ccc; width:80%;"')?></td>
			<td><?php echo form_input($action_field_name.'[width]', $width, 'style="border:1px solid #ccc; width:80%;"')?></td>
			<td><?php echo form_input($action_field_name.'[height]', $height, 'style="border:1px solid #ccc; width:80%;"')?></td>
			<td><?php echo form_input($action_field_name.'[quality]', $quality, 'style="border:1px solid #ccc; width:80%;"')?></td>
		</tr>
	</tbody>
</table>

<small><?php echo lang('ci:crop:standard_exp')?></small>