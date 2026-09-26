<?php

// icon.svg licensed under CC BY Font Awesome and has been modified
// https://fontawesome.com/license/free

include_once 'autoload.php';
$addonJson = json_decode(file_get_contents(__DIR__ . '/addon.json'));

return array(
    'name'              => $addonJson->name,
    'description'       => $addonJson->description,
    'version'           => $addonJson->version,
    'namespace'         => $addonJson->namespace,
    'author'            => 'EEHarbor',
    'author_url'        => 'https://eeharbor.com/',
    'docs_url'          => 'https://eeharbor.com/tag/documentation',
    'settings_exist'    => true,
    'models' => array(
        'BadTag'        => 'Model\BadTag',
        'Entry'         => 'Model\Entry',
        'Group'         => 'Model\Group',
        'Preference'    => 'Model\Preference',
        'TagTag'        => 'Model\TagTag',
    ),
    'commands' => [
        'tag:backup' => EEHarbor\Tag\Commands\CommandSyncTags::class,
    ],
);
