<?php
// system/user/config/datagrab_database.php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

$config['database']['datagrab'] = [
    'hostname' => 'zebra.hr',
    'username' => 'zebra_import',
    'password' => 'Gnw-QztsDR-2',
    'database' => 'zebra_import',
    'dbdriver' => 'mysqli',
    'dbprefix' => '',
    'pconnect' => false,
    'port'     => '',
];