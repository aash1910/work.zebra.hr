<?php if (ee()->session->flashdata('message_success')) { ?>
    <div class="alert alert--success inline success">
        <div class="alert__icon"><i class="fal fa-check-circle fa-fw"></i></div>
        <div class="alert__content">
            <p><?php echo ee()->session->flashdata('message_success');?></p>
        </div>
        <a href="javascript:void" class="alert__close close">
            <i class="fal fa-times alert__close-icon"></i>
        </a>
    </div>

<!--     <div class="alert inline success">
    <?php //echo ee()->session->flashdata('message_success');?>
    <a class="close" href="javascript:void"></a>
    </div> -->

<?php
} ?>

<div class="row pagehead">
    <div class="col-xs-6">
        <h2>
            <?=lang('store.settings.status')?>
        </h2>
    </div>
    <div class="col-xs-6" style="text-align:right; margin: 5px 0 15px 0;">
        <a href="<?= $edit_url ?>new" class="submit button button--primary"><?= lang('store.status_add') ?></a>
    </div>
</div>

<?= form_open($post_url) ?>

<?php
    $this->table->clear();
    $this->table->set_template($store_sortable_table_template);
    $this->table->set_heading(array(
        array('data' => '', 'width' => '20px'),
        array('data' => lang('name')),
        array('data' => lang('store.status_color')),
        array('data' => lang('store.status_email_ids')),
    ));

    foreach ($statuses as $status) {
        $status_name = $status->is_default ?
             '<strong><a href="'.$edit_url.$status->id.'">'.store_order_status_name($status->name).'</a></strong> <span class="status-default">('.lang('store.default').')</span>' :
             '<a href="'.$edit_url.$status->id.'">'.store_order_status_name($status->name).'</a>';

        $this->table->add_row(array(
            '<div class="store_sortable_handle"><i class="fal fa-bars"></i></div>',
            form_hidden('sorted_ids[]', $status->id).$status_name,
            $status->color ? '<span style="color:'.$status->color.'">'.$status->color.'</span>' : lang('store.default'),
            $status->getEmailNames(),
        ));
    }

    echo $this->table->generate();
?>

<?= form_close() ?>
