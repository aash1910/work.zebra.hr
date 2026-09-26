<?php $this->extend('_layouts/table_form_wrapper'); ?>

<h1><?php echo lang('demo_templates'); ?><br><i><?php echo lang('demo_description'); ?></i></h1>

<div class="demo-templates-table">
    <?php echo $this->embed('ee:_shared/table', $table); ?>
</div>