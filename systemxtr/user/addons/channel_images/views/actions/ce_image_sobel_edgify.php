<?php $thstyle='style="border-right-color:rgba(0, 0, 0, 0.1); border-right-style:solid; border-right-width:1px;"';?>
<table cellspacing="0" cellpadding="0" border="0" class="ChannelImagesTable CITable">
	<thead>
		<tr>
			<th><?php echo lang('ce:threshold')?></th>
			<th <?php echo $thstyle?>><?php echo lang('ce:foreground')?></th>
			<th <?php echo $thstyle?>><?php echo lang('ce:background')?></th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td><?php echo form_input($action_field_name.'[threshold]', $threshold, 'style="border:1px solid #ccc; width:80%;"')?></td>
			<td><?php echo form_input($action_field_name.'[foreground]', $foreground, 'style="border:1px solid #ccc; width:80%;"')?></td>
			<td><?php echo form_input($action_field_name.'[background]', $background, 'style="border:1px solid #ccc; width:80%;"')?></td>
		</tr>
	</tbody>
</table>

<small><?php echo lang('ce:sobel_edgify_exp')?></small>
<small>
<strong><?php echo lang('ce:threshold')?>:</strong> <?php echo lang('ce:threshold:exp')?> <br />
<strong><?php echo lang('ce:foreground')?>:</strong> <?php echo lang('ce:foreground:exp')?> <br />
<strong><?php echo lang('ce:background')?>:</strong> <?php echo lang('ce:background:exp')?> <br />
</small>