<?php if (ee()->session->flashdata('message_success')) { ?>
    <div class="alert alert--success success">
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
    <h2><?=lang('store.settings.order_fields')?></h2>
</div>
<?= form_open($post_url) ?>

<?php
    $this->table->clear();
    $this->table->set_template($store_table_template);
    $this->table->set_heading(
        lang('store.orders_field_name'),
        lang('title'),
        lang('store.mapped_member_field')
    );

    foreach ($order_fields as $field_name => $field) {
        $this->table->add_row(
            $field_name,
            isset($field['title']) ? form_input("order_fields[{$field_name}][title]", $field['title']) : lang('store.'.$field_name),
            $field_name == 'order_email' ? '' : form_dropdown("order_fields[{$field_name}][member_field]", $member_fields, $field['member_field'])
        );
    }

    echo $this->table->generate();
?>

<div style="text-align: right;">
    <?= form_submit(array('name' => 'submit', 'value' => lang('store.submit'), 'class' => 'submit button button--primary')) ?>
    <?= form_submit(array('name' => 'restore_defaults', 'value' => lang('store.restore_defaults'), 'class' => 'submit button button--secondary')) ?>
</div>

<?= form_close() ?>
