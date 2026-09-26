<?php if ($missing_settings == TRUE): ?>
<span style="font-weight:bold; color:red;"> <?php echo lang('ci:missing_settings')?> </span>
<input name="<?php echo $field_name?>[skip]" type="hidden" value="y" />
<?php else: ?>

<div class="CIField <?php if (REQ == 'PAGE') echo 'cisaef';?>" id="ChannelImages_<?php echo $field_id?>" data-fieldid="<?php echo $field_id?>">
<div class="CIDragDrop" id="CIDragDrop_<?php echo $field_id?>"><p><?php echo lang('ci:drophere')?></p></div>
<?php if ($this->config->item('is_site_on') != 'y'):?><p style="color:red; font-weight:bold;"><?php echo lang('ci:site_is_offline')?></p><?php endif;?>

<div class="TopActions"></div>
<table cellspacing="0" cellpadding="0" border="0" class="CITable">
	<thead>
		<tr>
			<th colspan="99" class="top_actions">
				<div class="block UploadImages"><?php echo lang('ci:upload_images')?><em id="ChannelImagesSelect_<?php echo $field_id?>"></em></div>
				<?php if ($settings['show_stored_images'] == 'yes'):?><div class="block StoredImages"><?php echo lang('ci:stored_images')?></div><?php endif;?>
				<?php if ($settings['show_import_files'] == 'yes'):?><div class="block ImportImages"><?php echo lang('ci:import_files')?></div><?php endif;?>
				<div class="block">&nbsp;</div>
				<div class="block_long">
					<div class="UploadProgress cihidden">
						<div class="progress">
							<div class="inner">
								<span class="percent"></span>&nbsp;&nbsp;&nbsp;
								<span class="speed"></span>&nbsp;&nbsp;&nbsp;
								<span class="bytes"> <span class="uploadedBytes"></span> <span class="totalBytes"></span> </span>&nbsp;&nbsp;&nbsp;
							</div>
						</div>
					</div>
				</div>
				<div class="block"><a href="#" class="StopUpload"><?php echo lang('ci:stop_upload')?></a></div>
			</th>
		</tr>
		<tr class="SearchImages" style="display:none">
			<th colspan="99">
			<?php if ($settings['stored_images_search_type'] == 'entry'):?>
				<table>
					<tbody>
						<tr>
							<td class="entryfilter">
								<div class="filter">
									<div class="left">
										<!--[if IE]>
										<input type="text" value="<?php echo lang('ci:filter_keywords')?>" maxlength="256" onblur="if (value == '') {value='<?php echo lang('ci:filter_keywords')?>'}" onfocus="if (value == '<?php echo lang('ci:filter_keywords')?>') {value =''}">
										<![endif]-->
										<!--[if !IE]> -->
										<input type="text" value="" maxlength="256" placeholder="<?php echo lang('ci:filter_keywords')?>">
										<![endif]-->
									</div>
									<div class="right">
										<label><?php echo lang('ci:last')?></label>
										<select><option>100</option><option>200</option><option>300</option><option>400</option><option>500</option></select>
										<label><?php echo lang('ci:entries')?></label>
									</div>
								</div>
								<div class="entries">
									<p class="Loading"><?php echo lang('ci:loading_entries')?></p>
								</div>
							</td>
							<td class="entryimages">
								<div class="filter">
									<div class="left"><h4><?php echo lang('ci:entry_images')?></h4></div>
									<div class="right"><p class="SearchingForImages"><?php echo lang('ci:searching_images')?></p></div>
								</div>
								<div class="images">
									<p class="NoEntrySelect"><?php echo lang('ci:no_entry_sel')?></p>
								</div>
							</td>
						</tr>
					</tbody>
				</table>
				<?php else:?>
				<table>
					<tbody>
						<tr>
							<td class="imagefilter">
								<div class="filter">
									<div class="left">
										<?php foreach ($settings['columns'] as $type => $val):?>
										<?php	if ($val == FALSE) continue;
												if ($type == 'row_num' OR $type == 'id' OR $type == 'image') continue;
										?>
										<!--[if IE]>
										<input rel="<?php echo $type?>" type="text" value="<?php echo $val?>" maxlength="256" onblur="if (value == '') {value='<?php echo $val?>'}" onfocus="if (value == '<?php echo $val?>') {value =''}">
										<![endif]-->
										<!--[if !IE]> -->
										<input rel="<?php echo $type?>" type="text" value="" maxlength="256" placeholder="<?php echo $val?>">
										<![endif]-->
										<?php endforeach;?>
									</div>
									<div class="right">
										<label><?php echo lang('ci:last')?></label>
										<select rel="limit"><option>50</option><option>75</option><option>100</option><option>150</option><option>200</option><option>500</option><option>1000</option><option>2500</option></select>
										<label><?php echo lang('ci:images')?></label>
									</div>
									<br clear="all">
								</div>
								<div class="images">
									<p class="Loading"><?php echo lang('ci:loading_images')?></p>
								</div>
							</td>
						</tr>
					</tbody>
				</table>
				<?php endif;?>
			</th>
		</tr>
		<tr class="ImageQueue cihidden"><th colspan="99"></th></tr>
<?php if ($settings['view_mode'] == 'table'): ;?>
		<tr>
			<?php foreach ($settings['columns'] as $type => $val):?>
			<?php if ($val == FALSE) continue;?>
			<?php $size=''; if ($type == 'row_num') $size = '10'; elseif ($type == 'id') $size = '20'; elseif ($type == 'image') $size = '50';?>
			<th style="width:<?php echo $size?>px"><?php echo $val?></th>
			<?php endforeach;?>

			<th style="width:110px"><?php echo lang('ci:actions')?></th>
		</tr>
<?php endif;?>
	</thead>

<?php if ($settings['view_mode'] == 'table'):?>
	<tbody class="AssignedImages TableBased">
	<?php if ($total_images < 1):?><tr class="NoImages"><td colspan="99"><?php echo lang('ci:no_images')?></td></tr><?php endif;?>
	</tbody>
<?php else:?>
	<tbody><tr><td class="TileBased" colspan="99">
	<ul class="AssignedImages">
	<?php if ($total_images < 1):?><p class="NoImages"><?php echo lang('ci:no_images')?></p><?php endif;?>
	</ul>
	<br clear="all">
	</td></tr></tbody>
<?php endif;?>

	<tfoot>
		<tr>
			<td <?php if ($settings['image_limit'] == '999999') echo 'style="display:none"';?> colspan="99" class="ImageLimit"><?php echo lang('ci:image_remain')?> <span class="remain"><?php echo $settings['image_limit']?></span></td>
		</tr>
	</tfoot>
</table>
       <input name="locationtype" id="locationtype" type="hidden" value="<?php echo $settings['upload_location']?>" class=""/>
	<input name="<?php echo $field_name?>[key]" type="hidden" value="<?php echo $temp_key?>" class="temp_key"/>
	<?php if (isset($actions) == TRUE):?>
	<div class="PerImageActionHolder cihidden"><?php echo base64_encode($this->load->view('pbf_per_image_action', array(), TRUE))?></div>
	<?php endif;?>
</div>

<?php endif; ?>

<script type="text/javascript">

var ChannelImages = ChannelImages ? ChannelImages : new Object();
ChannelImages.Fields = ChannelImages.Fields ? ChannelImages.Fields : new Object();
ChannelImages.Fields.Field_<?php echo $field_id?> = "<?php echo $field_json?>";
var countImg = 0;
</script>

<?php echo $this->load->view('pbf/js_templates');?>
