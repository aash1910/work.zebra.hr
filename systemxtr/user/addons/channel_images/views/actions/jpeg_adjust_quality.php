<?php $thstyle='style="border-right-color:rgba(0, 0, 0, 0.1); border-right-style:solid; border-right-width:1px;"';?>
<table cellspacing="0" cellpadding="0" border="0" class="ChannelImagesTable CITable">
	<thead>
		<tr>
			<th <?php echo $thstyle?>><?php echo lang('ci:quality')?></th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td><?php echo form_input($action_field_name.'[quality]', $quality, 'style="border:1px solid #ccc; width:80%;"')?></td>
		</tr>
	</tbody>
</table>
