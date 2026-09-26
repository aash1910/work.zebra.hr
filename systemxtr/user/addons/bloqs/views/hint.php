<?php
use BoldMinded\Bloqs\Library\Basee\App;

if ($name &&
    App::isFeatureAvailable('fieldNameHints') &&
    ee()->session->getMember()->PrimaryRole->RoleSettings->filter(
        'site_id',
        ee()->config->item('site_id')
    )->first()->show_field_names == 'y'
) {
    $partialName = App::isFeatureAvailable('generators') ? 'name_badge_copy' : 'field_name_badge';
    echo ee('View')->make('publish/partials/' . $partialName)->render(['name' => $name]);
}
