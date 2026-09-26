<tr class='Image <?php if ($cover == 1) echo 'PrimaryImage';?> <?php if ($image_id == 0) echo 'NewImg'?>'>
<?php if ($settings['columns']['row_num']):?> <td class="num">  </td> <?php endif;?>
<?php if ($settings['columns']['id']):?> <td><?php echo $image_id?></td> <?php endif;?>
<?php if ($settings['columns']['image']):?> <td> <a href='<?php echo $big_img_url?>' class='ImgUrl' rel='ChannelImagesGal' title='<?php echo $title?>'><img src="<?php echo $small_img_url?>" width='50px' alt='<?php echo $title?>'/></a> </td> <?php endif;?>
<?php if ($settings['columns']['filename']):?> <td><?php echo $filename?></td> <?php endif;?>
<?php if ($settings['columns']['title']):?> <td rel='title'><?php echo $title?></td> <?php endif;?>
<?php if ($settings['columns']['url_title']):?> <td rel='url_title'><?php echo $url_title?></td> <?php endif;?>
<?php if ($settings['columns']['desc']):?>  <td rel='desc'><?php echo $description?></td> <?php endif;?>
<?php if ($settings['columns']['category']):?> <td rel='category'><?php echo $category?></td> <?php endif;?>
<?php if ($settings['columns']['cifield_1']):?> <td rel='cifield_1'><?php echo $cifield_1?></td> <?php endif;?>
<?php if ($settings['columns']['cifield_2']):?> <td rel='cifield_2'><?php echo $cifield_2?></td> <?php endif;?>
<?php if ($settings['columns']['cifield_3']):?> <td rel='cifield_3'><?php echo $cifield_3?></td> <?php endif;?>
<?php if ($settings['columns']['cifield_4']):?> <td rel='cifield_4'><?php echo $cifield_4?></td> <?php endif;?>
<?php if ($settings['columns']['cifield_5']):?> <td rel='cifield_5'><?php echo $cifield_5?></td> <?php endif;?>
	<td>
		<?php if ($settings['allow_per_image_action'] == 'yes' && $linked == FALSE):?><a href='#' class='gIcon ImageProcessAction' title='Process Action' ></a><?php endif;?>
		<a href='javascript:void(0)' class='gIcon ImageMove' title='Move' ></a>
		<a href='#' class='gIcon <?php if ($cover == 1) echo 'StarIcon'; else echo 'StarGreyIcon';?> ImageCover' title='Cover'></a>
		<a href='#' <?php if (isset($linked) == TRUE && $linked == TRUE):?>class='gIcon ImageDel ImageLinked' title='Unlink'<?php else:?> class='gIcon ImageDel' title='Delete'<?php endif;?>></a>
		<div class='cihidden inputs'>
		<?php if ($image_id > 0 OR (isset($form_error) == TRUE && $form_error == TRUE)):?>
			<input name="field_id_<?php echo $field_id?>[images][<?php echo $image_order?>][title]" value="<?php echo $title?>" class="title">
			<input name="field_id_<?php echo $field_id?>[images][<?php echo $image_order?>][url_title]" value="<?php echo $url_title?>" class="url_title">
			<textarea name="field_id_<?php echo $field_id?>[images][<?php echo $image_order?>][desc]" class="desc"><?php echo $description?></textarea>
			<input name="field_id_<?php echo $field_id?>[images][<?php echo $image_order?>][category]" value="<?php echo $category?>" class="category">
			<input name="field_id_<?php echo $field_id?>[images][<?php echo $image_order?>][cifield_1]" value="<?php echo $cifield_1?>" class="cifield_1">
			<input name="field_id_<?php echo $field_id?>[images][<?php echo $image_order?>][cifield_2]" value="<?php echo $cifield_2?>" class="cifield_2">
			<input name="field_id_<?php echo $field_id?>[images][<?php echo $image_order?>][cifield_3]" value="<?php echo $cifield_3?>" class="cifield_3">
			<input name="field_id_<?php echo $field_id?>[images][<?php echo $image_order?>][cifield_4]" value="<?php echo $cifield_4?>" class="cifield_4">
			<input name="field_id_<?php echo $field_id?>[images][<?php echo $image_order?>][cifield_5]" value="<?php echo $cifield_5?>" class="cifield_5">
			<input name="field_id_<?php echo $field_id?>[images][<?php echo $image_order?>][imageid]" value="<?php echo $image_id?>" class="imageid">
			<input name="field_id_<?php echo $field_id?>[images][<?php echo $image_order?>][filename]" value="<?php echo $filename?>" class="filename">
			<input name="field_id_<?php echo $field_id?>[images][<?php echo $image_order?>][cover]" value="<?php echo $cover?>" class="cover">
		<?php else:?>
			#REPLACE#
		<?php endif;?>
		</div>
	</td>
</tr>
