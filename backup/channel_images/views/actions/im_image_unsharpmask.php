<?php $thstyle='style="border-right-color:rgba(0, 0, 0, 0.1); border-right-style:solid; border-right-width:1px;"';?>
<table cellspacing="0" cellpadding="0" border="0" class="ChannelImagesTable CITable">
	<thead>
		<tr>
			<th><?php echo lang('im:radius')?></th>
			<th <?php echo $thstyle?>><?php echo lang('im:sigma')?></th>
			<th <?php echo $thstyle?>><?php echo lang('im:amount')?></th>
			<th <?php echo $thstyle?>><?php echo lang('im:threshold')?></th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td><?php echo form_input($action_field_name.'[radius]', $radius, 'style="border:1px solid #ccc; width:80%;"')?></td>
			<td><?php echo form_input($action_field_name.'[sigma]', $sigma, 'style="border:1px solid #ccc; width:80%;"')?></td>
			<td><?php echo form_input($action_field_name.'[amount]', $amount, 'style="border:1px solid #ccc; width:80%;"')?></td>
			<td><?php echo form_input($action_field_name.'[threshold]', $threshold, 'style="border:1px solid #ccc; width:80%;"')?></td>
		</tr>
	</tbody>
</table>

<small>

<strong><?php echo lang('im:radius')?>:</strong> <?php echo lang('im:radius:exp')?> <br />
<strong><?php echo lang('im:sigma')?>:</strong> <?php echo lang('im:sigma:exp')?> <br />
<strong><?php echo lang('im:amount')?>:</strong> <?php echo lang('im:amount:exp')?> <br />
<strong><?php echo lang('im:threshold')?>:</strong> <?php echo lang('im:threshold:exp')?> <br />
</small>
