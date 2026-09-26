<?php $thstyle='style="border-right-color:rgba(0, 0, 0, 0.1); border-right-style:solid; border-right-width:1px;"';?>
<table cellspacing="0" cellpadding="0" border="0" class="ChannelImagesTable CITable">
	<thead>
		<tr>
			<th><?php echo lang('ce:gap_height')?></th>
			<th <?php echo $thstyle?>><?php echo lang('ce:start_opacity')?></th>
			<th <?php echo $thstyle?>><?php echo lang('ce:end_opacity')?></th>
			<th <?php echo $thstyle?>><?php echo lang('ce:reflection_height')?></th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td><?php echo form_input($action_field_name.'[gap_height]', $gap_height, 'style="border:1px solid #ccc; width:80%;"')?></td>
			<td><?php echo form_input($action_field_name.'[start_opacity]', $start_opacity, 'style="border:1px solid #ccc; width:80%;"')?></td>
			<td><?php echo form_input($action_field_name.'[end_opacity]', $end_opacity, 'style="border:1px solid #ccc; width:80%;"')?></td>
			<td><?php echo form_input($action_field_name.'[reflection_height]', $reflection_height, 'style="border:1px solid #ccc; width:80%;"')?></td>
		</tr>
	</tbody>
</table>

<small><?php echo lang('ce:reflection_exp')?></small>
<small>
<strong><?php echo lang('ce:gap_height')?>:</strong> <?php echo lang('ce:gap_height:exp')?> <br />
<strong><?php echo lang('ce:start_opacity')?>:</strong> <?php echo lang('ce:start_opacity:exp')?> <br />
<strong><?php echo lang('ce:end_opacity')?>:</strong> <?php echo lang('ce:end_opacity:exp')?> <br />
<strong><?php echo lang('ce:reflection_height')?>:</strong> <?php echo lang('ce:reflection_height:exp')?> <br />
</small>