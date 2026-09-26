<?php

require_once 'autoload.php';
$addonJson = json_decode(file_get_contents(__DIR__ . '/addon.json'));

return array(
    'name'              => $addonJson->name,
    'description'       => $addonJson->description,
    'version'           => $addonJson->version,
    'namespace'         => $addonJson->namespace,
    'author'            => 'EEHarbor',
    'author_url'        => 'http://eeharbor.com/fieldpack',
    'docs_url'          => 'http://eeharbor.com/fieldpack/documentation',
    'settings_exist'    => false,
    'fieldtypes'        => array(
      'fieldpack_checkboxes' => array(
        'name' => 'Field Pack - Checkboxes'
      ),
      'fieldpack_dropdown' => array(
        'name' => 'Field Pack - Dropdown'
      ),
      'fieldpack_list' => array(
        'name' => 'Field Pack - List'
      ),
      'fieldpack_multiselect' => array(
        'name' => 'Field Pack - Multiselect'
      ),
      'fieldpack_pill' => array(
        'name' => 'Field Pack - Pill'
      ),
      'fieldpack_radio_buttons' => array(
        'name' => 'Field Pack - Radio Buttons'
      ),
      'fieldpack_switch' => array(
        'name' => 'Field Pack - Switch'
      )
    )
);
