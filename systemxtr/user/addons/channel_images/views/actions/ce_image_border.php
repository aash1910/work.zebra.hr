<?php $thstyle='style="border-right-color:rgba(0, 0, 0, 0.1); border-right-style:solid; border-right-width:1px;"';?>
<table cellspacing="0" cellpadding="0" border="0" class="ChannelImagesTable CITable">
	<thead>
		<tr>
			<th><?php echo lang('ce:thickness') ?></th>
			<th <?php echo $thstyle; ?>><?php echo lang('ce:color')?></th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td><?php echo form_input($action_field_name.'[thickness]', $thickness, 'style="border:1px solid #ccc; width:80%;"')?></td>
			<td><?php echo form_input($action_field_name.'[color]', $color, 'style="border:1px solid #ccc; width:80%;"')?></td>
		</tr>
	</tbody>
</table>
<div style="text-align: justify">
<em><?php echo lang('ce:border_exp') ?></em>
<em>
<strong><?php echo lang('ce:thickness')?>:</strong> <?php echo lang('ce:thickness:exp')?> <br />
<strong><?php echo lang('ce:color')?>:</strong> <?php echo lang('ce:color:exp')?> <br />
</em>
</div>
<!--</div> -->