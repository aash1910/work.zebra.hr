<?php

return array(
    'author'      => 'WMD',
    'author_url'  => 'https://wmd.hr/',
    'name'        => 'Store :: Orders',
    'description' => 'Generates order CSV files on order completion and on manual Azuriraj status update.',
    'version'     => '2.0.0',
    'namespace'   => 'Wmd\StoreOrders',

    // Declares both hooks — EE7 registers these automatically via migrations.
    // No need for manual activate_extension() DB inserts.
    'extensions'  => array(
        array(
            'hook'     => 'store_order_complete_end',
            'method'   => 'order_complete',
            'priority' => 10,
        ),
        array(
            'hook'     => 'store_order_update_status_end',
            'method'   => 'order_status_update',
            'priority' => 10,
        ),
    ),
);
