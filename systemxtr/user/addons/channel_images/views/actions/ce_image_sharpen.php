<?php $thstyle='style="border-right-color:rgba(0, 0, 0, 0.1); border-right-style:solid; border-right-width:1px;"';?>
<table cellspacing="0" cellpadding="0" border="0" class="ChannelImagesTable CITable">
	<thead>
		<tr>
			<th><?php echo lang('ce:amount')?></th>
			<th <?php echo $thstyle?>><?php echo lang('ce:radius')?></th>
			<th <?php echo $thstyle?>><?php echo lang('ce:threshold')?></th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td><?php echo form_input($action_field_name.'[amount]', $amount, 'style="border:1px solid #ccc; width:80%;"')?></td>
			<td><?php echo form_input($action_field_name.'[radius]', $radius, 'style="border:1px solid #ccc; width:80%;"')?></td>
			<td><?php echo form_input($action_field_name.'[threshold]', $threshold, 'style="border:1px solid #ccc; width:80%;"')?></td>
		</tr>
	</tbody>
</table>

<small><?php echo lang('ce:sharpen_exp')?></small>
<small>
<strong><?php echo lang('ce:amount')?>:</strong> <?php echo lang('ce:amount:exp')?> <br />
<strong><?php echo lang('ce:radius')?>:</strong> <?php echo lang('ce:radius:exp')?> <br />
<strong><?php echo lang('ce:threshold')?>:</strong> <?php echo lang('ce:threshold_sharpen:exp')?> <br />
</small>
