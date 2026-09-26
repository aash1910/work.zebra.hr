<div class="fields-grid-setup ui-sortable" data-group="grid"><!-- grid-wrap -->
    <?php foreach ($columns as $column): ?>
        <?php echo $column?>
    <?php endforeach ?>
</div>

<?php
$identifier = $eeVersionNumber >= 5 ? 'class="grid-col-settings-elements"' : 'id="grid_col_settings_elements"';
$hidden = $eeVersionNumber >= 5 ? 'style="display: none;"' : '';
?>

<div <?php echo $identifier ?> data-group="always-hidden" class="hidden" <?php echo $hidden ?>>
    <?php echo $blank_col?>

    <?php foreach ($settings_forms as $form): ?>
        <?php echo $form?>
    <?php endforeach ?>
</div>
