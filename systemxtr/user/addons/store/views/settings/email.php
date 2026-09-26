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
<?= form_open($post_url) ?>

<div class="row pagehead">
    <div class="col-xs-6">
        <h2 class="title-bar__title">
            <?=lang('store.settings.email')?>
        </h2>
    </div>
    <div class="col-xs-6" style="text-align: right; margin: 5px 0 15px 0;">
        <a href="<?= $edit_url ?>new" class="submit button button--primary"><?= lang('store.new_email_template') ?></a>
    </div>
</div>

<?php

    $this->table->clear();
    $this->table->set_template($store_table_template);
    $this->table->set_heading(
        array('data'=>lang('store.email_name'),'width' => '25%'),
        array('data'=>lang('store.email_subject'), 'width' => '25%'),
        array('data'=>lang('store.status'), 'width' => '25%'),
        array('data' => form_checkbox(array('id' => 'checkall')), 'width' => '15%')
    );

    $i = 0;
    foreach ($emails as $email) {
        $this->table->add_row(
            '<a href="'.$edit_url.$email->id.'">'.store_email_template_name($email->name).'</a>',
            $email->subject,
            store_enabled_str($email->enabled),
            form_checkbox('selected[]', $email->id, false)
        );
    }

    echo $this->table->generate();
?>

<div style="text-align: right;">
    <?= form_dropdown('with_selected', array('enable' => lang('store.enable_selected'), 'disable' => lang('store.disable_selected'), 'delete' => lang('store.delete_selected'))) ?>
    <?= form_submit(array('name' => 'submit', 'value' => lang('store.submit'), 'class' => 'submit button button--primary')) ?>
</div>

<?= form_close() ?>
