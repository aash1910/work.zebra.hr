<?php $this->extend('_layouts/table_form_wrapper'); ?>

<p class="notice"><?php echo lang('locked_tag_group_description'); ?></p>

<!-- using $this and not $caller on purpose here -->
<?php echo $this->embed('ee:_shared/table', $tag_group_table); ?>

