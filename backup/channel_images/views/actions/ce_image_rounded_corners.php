<?php $thstyle='style="border-right-color:rgba(0, 0, 0, 0.1); border-right-style:solid; border-right-width:1px;"';?>
<table cellspacing="0" cellpadding="0" border="0" class="ChannelImagesTable CITable">
	<thead>
		<tr>
			<th><?php echo lang('ce:corner_identifier')?></th>
			<th <?php echo $thstyle?>><?php echo lang('ce:radius')?></th>
			<th <?php echo $thstyle?>><?php echo lang('ce:rccolor')?></th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td><?php echo form_input($action_field_name.'[corner_identifier]', $corner_identifier, 'style="border:1px solid #ccc; width:80%;"')?></td>
			<td><?php echo form_input($action_field_name.'[radius]', $radius, 'style="border:1px solid #ccc; width:80%;"')?></td>
			<td><?php echo form_input($action_field_name.'[color]', $color, 'style="border:1px solid #ccc; width:80%;"')?></td>
		</tr>
	</tbody>
</table>

<small><?php echo lang('ce:rounded_corners_exp')?></small>
<small>
<strong><?php echo lang('ce:corner_identifier')?>:</strong> <?php echo lang('ce:corner_identifier:exp')?> <br />
<strong><?php echo lang('ce:radius')?>:</strong> <?php echo lang('ce:radius:exp')?> <br />
<strong><?php echo lang('ce:rccolor')?>:</strong> <?php echo lang('ce:rccolor:exp')?> <br />
</small>