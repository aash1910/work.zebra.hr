<?php if (ee()->session->flashdata('message_success')) { ?>
    <div class="alert alert--success">
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
    <h2><?=lang('store.settings.country')?></h2>
</div>
<?= form_open($post_url) ?>
<fieldset style="margin-bottom: 1em">
    <legend><?= lang('store.defaults') ?></legend>
    <table>
        <tr>
            <td>
                <?= lang('store.settings.default_country').':  '?>
            </td>
            <td>
                <select name="default[country_code]" class="store_country_select" style="width: 100%"><?= $country_options ?></select>
            </td>
        </tr>
        <tr>
            <td>
                <?= lang('store.settings.default_state').':  '?>
            </td>
            <td>
                <select name="default[state_code]" class="store_state_select" style="width: 100%"><?= $state_options ?></select>
            </td>
        </tr>
    </table>
    <p style="text-align: right;">
        <?= form_submit(array('name' => 'submit_default', 'value' => lang('store.submit'), 'class' => 'submit button button--primary')) ?>
    </p>
</fieldset>
<?= form_close() ?>

<?= form_open($post_url) ?>

<?php
    $this->table->clear();
    $this->table->set_template($store_table_template);
    $this->table->set_heading(
        lang('store.country'),
        lang('store.code'),
        lang('store.status'),
        array('data' => form_checkbox(array('id' => 'checkall')), 'width' => '60px')
    );

    foreach ($countries as $country) {
        $this->table->add_row(
            '<a href="'.$edit_url.$country->id.'">'.$country->name.'</a>',
            $country->code,
            store_enabled_str($country->enabled),
            form_checkbox('selected[]', $country->id)
        );
    }

    echo $this->table->generate();
?>

<div style="text-align: right;">
    <?= form_dropdown('with_selected', array('enable' => lang('store.enable_selected'), 'disable' => lang('store.disable_selected'))) ?>
    <?= form_submit(array('name' => 'submit', 'value' => lang('store.submit'), 'class' => 'submit button button--primary')) ?>
</div>

<?= form_close() ?>
