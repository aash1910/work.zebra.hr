<div class="row pagehead">
    <h2><?=lang('store.settings.security')?></h2>
</div>
<?= form_open($post_url) ?>
<?php
    $roles = array('');

    // TODO
    foreach ($member_roles as $role_id => $role_title) {
        $roles[] = '<div style="text-align: center">'.$role_title.'</div>';
    }

    $this->table->clear();
    $this->table->set_template($store_table_template);
    $this->table->set_heading($roles);

    foreach ($security as $privilege => $values) {
        $row = array(lang('store.'.$privilege));
        foreach ($member_roles as $role_id => $role_title) {
            if ($role_id == 1) {
                $row[] = '<div style="text-align: center">'.form_checkbox(array('checked' => true, 'disabled' => 'disabled')).'</div>';
            } else {
                $row[] = '<div style="text-align: center">'.form_checkbox('security['.$privilege.'][]', $role_id, in_array($role_id, $values)).'</div>';
            }
        }
        $this->table->add_row($row);
    }

    echo $this->table->generate();
?>

<div style="text-align: right;">
    <?= form_submit(array('name' => 'submit', 'value' => lang('store.submit'), 'class' => 'submit button button--primary')); ?>
</div>
<?= form_close() ?>
