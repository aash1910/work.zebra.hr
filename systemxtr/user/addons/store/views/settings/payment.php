<div class="row pagehead">
    <h2><?=lang('store.settings.payment')?></h2>
</div>
<?php

    $this->table->clear();
    $this->table->set_template($store_table_template);
    $this->table->set_heading(
        array('data' => lang('store.payment_method'), 'style' => 'width:40%'),
        array('data' => lang('store.short_name'), 'style' => 'width:40%'),
        array('data' => lang('store.status'), 'style' => 'width:20%')
    );

    foreach ($gateways as $gateway) {
        $this->table->add_row(
            '<a href="'.$gateway['settings_url'].'">'.$gateway['title'].'</a>',
            $gateway['class'],
            $gateway['enabled'] ?
                '<span class="store_payment_enabled">'.store_enabled_str($gateway['enabled']).'</span>' :
                store_enabled_str($gateway['enabled'])
        );
    }

    echo $this->table->generate();
