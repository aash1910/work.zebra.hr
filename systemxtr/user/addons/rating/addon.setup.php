<?php

require_once 'autoload.php';
$addonJson = json_decode(file_get_contents(__DIR__ . '/addon.json'));

return array(
    'name'              => $addonJson->name,
    'description'       => $addonJson->description,
    'version'           => $addonJson->version,
    'namespace'         => $addonJson->namespace,
    'author'            => 'EEHarbor',
    'author_url'        => 'http://eeharbor.com/rating',
    'docs_url'          => 'http://eeharbor.com/rating/documentation',
    'settings_exist'    => true,
    'models' => array(
        'Field'         => 'Model\Field',
        'Param'         => 'Model\Param',
        'Preference'    => 'Model\Preference',
        'Quarantine'    => 'Model\Quarantine',
        'Rating'        => 'Model\Rating',
        'Review'        => 'Model\Review',
        'Stat'          => 'Model\Stat',
        'Template'      => 'Model\Template',
    ),
    'models.dependencies' => array(
        'Rating'   => array(
            'ee:ChannelEntry'
        ),
        'Stat'   => array(
            'ee:ChannelEntry'
        )
    ),
    'aliases' => [
        'ExpressionEngine\Model\Channel\ChannelEntry',
    ],
);
