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
<!--
    <div class="alert inline success">
    <?php //echo ee()->session->flashdata('message_success');?>
    <a class="close" href="javascript:void"></a>
    </div> -->

<?php
} ?>
<div class="row pagehead">
    <h2><?=lang('store.add_payment_method')?></h2>
</div>
<?= form_open($post_url); ?>

<?php
    $this->table->clear();
    $this->table->set_template($store_table_template);
    $this->table->set_heading(
        array('data' => '', 'style' => 'width:40%'),
        array('data' => '')
    );

    $this->table->add_row(
        lang('store.payment_method', 'payment_method'),
        $title
    );

    $this->table->add_row(
        lang('store.short_name', 'short_name'),
        $short_name
    );

    foreach ($default_settings as $key => $default) {
        // Special handling for Stripe: show Secret Key and Publishable Key
        if ($short_name === 'Stripe_PaymentIntents' && $key === 'apiKey') {
            $this->table->add_row(
                '<strong>Secret Key</strong>',
                store_setting_input('apiKey', $default, $settings['apiKey'])
            );
            if (isset($default_settings['publishableKey'])) {
                $this->table->add_row(
                    '<strong>Publishable Key</strong>',
                    store_setting_input('publishableKey', $default_settings['publishableKey'], $settings['publishableKey'])
                );
            }
            continue;
        }
        if ($short_name === 'Stripe_PaymentIntents' && $key === 'publishableKey') {
            // Already handled above
            continue;
        }
        $this->table->add_row(
            '<strong>'.lang(\Store\Dependency\Illuminate\Support\Str::snake("store.payment.$key"), "settings_$key").'</strong>',
            store_setting_input($key, $default, $settings[$key])
        );
    }

    $this->table->add_row(
        lang('store.enabled', 'payment_method_enabled'),
        store_form_checkbox('enabled', $enabled)
    );

    echo $this->table->generate();
?>

<p class="required-field"><strong class="notice">*</strong> <?= lang('required_fields') ?></p>
<div style="text-align: right;">
    <?= form_submit(array('name' => 'submit', 'value' => lang('store.submit'), 'class' => 'submit button button--primary')); ?>
</div>

<?= form_close(); ?>
