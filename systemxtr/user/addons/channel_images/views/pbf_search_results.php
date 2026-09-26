<div class="ImagesResultTabs">

	<a href="#" class="ClearImageSearch ClearImageSearch_Top"><?php echo lang('ci:clear_search')?></a>

	<ul>
	<?php foreach($images as $channel_id => $row):?>
		<li><a href="#CITAB_<?php echo $field_id?>_<?php echo $channel_id?>"><?php echo $channels[$channel_id]?> (<?php echo count($row)?>)</a></li>
	<?php endforeach;?>
	</ul>

	<?php foreach($images as $channel_id => $row):?>

	<div id="CITAB_<?php echo $field_id?>_<?php echo $channel_id?>">
		<table cellspacing="0" cellpadding="0" class="CITable"> <thead> <tr>
			<th width="30">&nbsp;</th>
			<th><span><?php echo lang('ci:image')?></span></th>
			<th><span><?php echo lang('ci:title')?></span></th>
			<th><span><?php echo lang('ci:desc')?></span></th>
			<th><span><?php echo lang('ci:category')?></span></th>
			<th><span><?php echo lang('ci:filename')?></span></th>
			</tr> </thead> <tbody>

			<?php foreach($row as $image):?>
				<?php $image->image_id_hidden = $image->image_id;
				$image->image_id = 0;?>
				<tr>
					<td>
						<a href="#" class="AddImage">&nbsp;</a>
						<span class='imagetd cihidden'><?php echo base64_encode($this->view('pbf_field_single_image', $image, TRUE)); ?></span>
						<span class='imageinfo cihidden'><?php echo $this->channel_images_helper->generate_json($image);?></span>
					</td>
					<td><a href='<?php echo $image->big_img_url?>' class='ImgUrl' rel='CISearchResult' title="<?php echo $image->title?>"><img src='<?php echo $image->small_img_url?>' width='<?php echo ee('channel_images:Settings')->settings['image_preview_size']?>'/></a></td>
					<td><?php echo $image->title?></td>
					<td><?php echo $image->description?></td>
					<td><?php echo $image->category?></td>
					<td><?php echo $image->filename?></td>
				</tr>
			<?php endforeach;?>


		</tbody></table>
	</div>

	<?php endforeach;?>

	<a href="#" class="ClearImageSearch"><?php echo lang('ci:clear_search')?></a>
</div>
