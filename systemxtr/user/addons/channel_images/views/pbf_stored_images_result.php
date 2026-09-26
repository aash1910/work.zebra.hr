<?php foreach($images as $img):?>
	<?php $ID = $img->image_id;
	$img->image_id = 0;?>

	<div class="Image">
		<a href='#' class='ImgUrl' title="<?php echo $img->title?>" rel="<?php echo $img->filename?>" id="<?php echo $ID?>" data="<?php echo $img->entry_id?>"><img src='<?php echo $img->small_img_url?>' width='<?php echo ee('channel_images:Settings')->settings['image_preview_size']?>'/></a>
		<div class="cihidden"><?php echo base64_encode($this->view('pbf_field_single_image', $img, TRUE)); ?></div>
	</div>
<?php endforeach;?>

<?php if (empty($images) == TRUE):?> <p><?php echo lang('ci:no_images_found')?> <?php endif;?>

