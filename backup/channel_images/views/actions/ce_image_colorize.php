<?php
$alpha_enabled = version_compare(PHP_VERSION, '5.2.5');
?>


<?php $thstyle='style="border-right-color:rgba(0, 0, 0, 0.1); border-right-style:solid; border-right-width:1px;"';?>
<table cellspacing="0" cellpadding="0" border="0" class="ChannelImagesTable CITable">
	<thead>
		<tr>
			<th><?php echo lang('ce:red')?></th>
			<th <?php echo $thstyle?>><?php echo lang('ce:green')?></th>
			<th <?php echo $thstyle?>><?php echo lang('ce:blue')?></th>
			<?php if ($alpha_enabled >= 0):?><th <?php echo $thstyle?>><?php echo lang('ce:alpha')?></th><?php endif;?>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td><?php echo form_input($action_field_name.'[red]', $red, 'style="border:1px solid #ccc; width:80%;"')?></td>
			<td><?php echo form_input($action_field_name.'[green]', $green, 'style="border:1px solid #ccc; width:80%;"')?></td>
			<td><?php echo form_input($action_field_name.'[blue]', $blue, 'style="border:1px solid #ccc; width:80%;"')?></td>
			<?php if ($alpha_enabled >= 0):?><td><?php echo form_input($action_field_name.'[alpha]', $alpha, 'style="border:1px solid #ccc; width:80%;"')?></td></th><?php endif;?>
		</tr>
	</tbody>
</table>

<small><?php echo lang('ce:colorize_exp')?></small>
<small>
<strong><?php echo lang('ce:red')?>:</strong> <?php echo lang('ce:red:exp')?> <br />
<strong><?php echo lang('ce:green')?>:</strong> <?php echo lang('ce:green:exp')?> <br />
<strong><?php echo lang('ce:blue')?>:</strong> <?php echo lang('ce:blue:exp')?> <br />
<?php if ($alpha_enabled >= 0):?><strong><?php echo lang('ce:alpha')?>:</strong> <?php echo lang('ce:alpha:exp')?> <?php endif;?>
</small>
