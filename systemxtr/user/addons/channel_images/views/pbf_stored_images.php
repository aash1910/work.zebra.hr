<div id="CI_StoredImages">
	<div class="topbar">
		<h3><?php echo lang('ci:search_images')?></h3>
		<a href="javascript:$.fancybox.close()" class="close"><?php echo lang('close')?></a>
	</div>
	<div class="search">
		<?php echo lang('ci:find_entry')?> <input type="text" class="text"> <button><?php echo lang('ci:get_images')?></button>
		<input type="hidden" class="entry_id" value="">
	</div>
	<p class="loadingimages"><?php echo lang('ci:loading_images')?></p>
	<div class="result clearfix"></div>
</div>