<div class="CIField">
<table cellspacing="0" cellpadding="0" border="0" class="CITable" rel="<?php echo $field_id?>">
	<thead>
		<tr>
			<th><input type="checkbox" class="checkall" checked></th>
			<th><?php echo lang('ci:filename')?></th>
			<th><?php echo lang('ci:filesize')?></th>
		</tr>
	</thead>
	<tbody class="fileslist">
	<?php foreach ($files as $filename => $size):?>
		<tr>
			<td><input type="checkbox" value="<?php echo $filename?>" checked></td>
			<td><?php echo $filename?></td>
			<td><?php echo $size?></td>
		</tr>
	<?php endforeach;?>
	<?php if (count($files) < 1):?><tr><td colspan="99"><?php echo lang('ci:import:no_files')?></td></tr><?php endif;?>
	</tbody>
	<tfoot>
		<tr>
			<?php if (count($files) > 0):?><td colspan="99" style="text-align:left"><button class="ImportImagesBtn"><?php echo lang('ci:import_files')?> <span class="loading"></span></button></td><?php endif;?>
		</tr>
	</tfoot>
</table>
</div>
