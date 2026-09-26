<?php $thstyle='style="border-right-color:rgba(0, 0, 0, 0.1); border-right-style:solid; border-right-width:1px;"';?>
<table cellspacing="0" cellpadding="0" border="0" class="ChannelImagesTable CITable">
	<thead>
		<tr>
			<th><?php echo lang('ce:block_size')?></th>
			<th <?php echo $thstyle?>><?php echo lang('ce:advanced')?></th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td>
				<?php echo form_input($action_field_name.'[block_size]', $block_size, 'style="border:1px solid #ccc; width:80%;"')?>
				<small><?php echo lang('ce:block_size:exp')?></small>
			</td>
			<td>
				<?php echo form_dropdown($action_field_name.'[advanced]', array('no' => lang('no'), 'yes' => lang('yes')), $advanced); ?>
				<small><?php echo lang('ce:advanced:exp')?></small>
			</td>
		</tr>
	</tbody>
</table>

<small><?php echo lang('ce:pixelate_exp')?></small>

