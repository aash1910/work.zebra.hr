<div class="row pagehead">
    <h2><?=lang('store.settings.shipping')?></h2>
</div>
<?php $form = store_form(null, 'settings'); echo $form->open(); ?>
<fieldset class="col-group">
    <div class="setting-txt col w-8">
        <h3><?= lang('store.defaults') ?></h3>
        <em><?= lang('store.settings.default_shipping_method') ?></em>
    </div>
    <div class="setting-field col w-8 last">
        <?= $form->select(
            'store_default_shipping_method_id',
            $shipping_method_options,
            array('selected' => $default_shipping_method_id)
        ); ?>
        <?= form_submit(array('name' => 'submit_default', 'value' => lang('store.submit'), 'class' => 'button button--primary')) ?>
    </div>
</fieldset>
<?= $form->close() ?>

<div style="text-align: right; margin: 5px 0 15px 0;">
    <a href="<?= $edit_url ?>new" class="submit button button--primary"><?= lang('store.shipping_method_add') ?></a>
</div>

<?= form_open($post_url) ?>

<?php
    $this->table->clear();
    $this->table->set_template($store_sortable_table_template);
    $this->table->set_heading(
        array('data' => '&nbsp;', 'width' => '5%'),
        array('data' => '#', 'width' => '20px'),
        array('data' => lang('store.shipping_method'), 'width' => '60%'),
        array('data' => lang('store.status'), 'width' => '20%'),
        array('data' => form_checkbox(array('id' => 'checkall')), 'width' => '20px')
    );

    foreach ($shipping_methods as $method) {
        $this->table->add_row(
            '<div class="store_sortable_handle"><i class="fal fa-bars"></i></div>',
            $method->id,
            form_hidden('sorted_ids[]', $method->id).
            '<a href="'.$edit_url.$method->id.'">'.$method->name.'</a>',
            store_enabled_str($method->enabled),
            form_checkbox("selected[]", $method->id)
        );
    }

    echo $this->table->generate();
?>

<div style="text-align: right;">
    <?= form_dropdown('with_selected', array('enable' => lang('store.enable_selected'), 'disable' => lang('store.disable_selected'), 'delete' => lang('store.delete_selected'))) ?>
    <?= form_submit(array('name' => 'submit', 'value' => lang('store.submit'), 'class' => 'submit button button--primary')); ?>
</div>

<?= form_close() ?>

<hr>

<!-- Shipping Extensions Table -->
<p style="margin-top:2em; color: #666; font-size: 0.95em;">
    <em>Note: Shipping extensions are managed in their own extension settings pages. Use the links below to configure each shipping extension.</em>
</p>
<h3 style="margin-top:0.5em;">Shipping Extensions</h3>
<br>
<table class="store_table" style="width:100%;margin-bottom:2em;">
    <thead>
        <tr>
            <th>Class</th>
            <th>Version</th>
            <th>Status</th>
            <th>Settings</th>
        </tr>
    </thead>
    <tbody>
        <?php if (!empty($shipping_extensions)): ?>
            <?php foreach ($shipping_extensions as $ext): ?>
                <tr>
                    <td><?= htmlspecialchars($ext['class']) ?></td>
                    <td><?= htmlspecialchars($ext['version']) ?></td>
                    <td><?= $ext['enabled'] ? '<span style=\'color:green\'>Enabled</span>' : '<span style=\'color:red\'>Disabled</span>' ?></td>
                    <td>
                        <?php if ($ext['enabled'] && $ext['settings_url'] && $ext['is_installed']): ?>
                            <a href="<?= $ext['settings_url'] ?>">Settings</a>
                        <?php elseif ($ext['is_installed']): ?>
                            <span style="color:red">Not enabled</span>
                        <?php else: ?>
                            <span style="color:red">Not installed</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="4">No shipping extensions found.</td></tr>
        <?php endif; ?>
    </tbody>
</table>
