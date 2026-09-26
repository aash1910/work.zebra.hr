<div class="grid_col_settings_custom_field_<?php echo $col_type?>" data-fieldtype="<?php echo $col_type?>">
  <?php foreach ($col_settings as $name => $settings) {
    $this->embed('ee:_shared/form/section', array('name' => $name, 'settings' => $settings));
  }
  ?>
</div>
