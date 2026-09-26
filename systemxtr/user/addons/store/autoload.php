<?php

// -----------------------------------------
// PHP Requirements
// -----------------------------------------
if (version_compare(PHP_VERSION, '7.1', '<')) {
    show_error('Store requires PHP version 7.1+, you have ' . PHP_VERSION);
}

// don't check version inside installer, seems to break on some installs
if (defined('APP_VER') && !defined('EE_APPPATH') && version_compare(APP_VER, '5.0', '<')) {
    show_error('Expresso Store requires ExpressionEngine version 5.0+, you have ' . APP_VER);
}

if (!extension_loaded('curl')) {
    show_error('Expresso Store requires the PHP cURL extension to be installed on your server.');
}

// force PHP to use period as decimal point when formatting numbers
// (otherwise it causes SQL errors)
setlocale(LC_NUMERIC, 'C');

$composer = require __DIR__ . '/vendor-build/autoload.php';

if (!defined('STORE_AUTOLOADED')) {
    // autoload EE classes we might need
    if (defined('PATH_MOD')) {

        $composerClassMap = [
            'CI_Model'          => BASEPATH . 'core/Model.php',
            'Channel'           => PATH_MOD . 'channel/mod.channel.php',
            'Member'            => PATH_MOD . 'member/mod.member.php',
            'Member_register'   => PATH_MOD . 'member/mod.member_register.php',
        ];

        // Check to see if IP to nation is here:
        // PATH_MOD . 'ip_to_nation/models/ip_to_nation_data.php'
        // If not, check to see if it's here:
        // PATH_THIRD . 'ip_to_nation/models/ip_to_nation_data.php'
        if (file_exists(PATH_MOD . 'ip_to_nation/models/ip_to_nation_data.php')) {
            $composerClassMap['Ip_to_nation_data'] = PATH_MOD . 'ip_to_nation/models/ip_to_nation_data.php';
        } else if (file_exists(PATH_THIRD . 'ip_to_nation/models/ip_to_nation_data.php')) {
            $composerClassMap['Ip_to_nation_data'] = PATH_THIRD . 'ip_to_nation/models/ip_to_nation_data.php';
        }

        $composer->addClassMap($composerClassMap);
    }

    // only initialize Store if called from EE or install wizard
    if (defined('APPPATH')) {
        $ee = ee();

        // load language file
        ee()->lang->loadfile('store');

        // support servers with PDO disabled
        if (!class_exists('PDO')) {
            class_alias('Store\Dependency\Illuminate\CodeIgniter\FakePDO', 'PDO');
        }

        // avoids PHP < 5.3 parse errors
        $container = 'Store\Container';
        ee()->set('store', new \Store\Container($ee));

        if(!isset(ee()->store) || is_null(ee()->store)) {
            ee()->store = new \Store\Container($ee);
        }
        if(!isset(ee()->store) || is_null(ee()->store)) {
            ee()->setMock('store', new \Store\Container($ee));
        }

        ee()->store->set_composer($composer);
        ee()->store->initialize();
    }

    define('STORE_AUTOLOADED', true);
}

return ee()->store->get_composer();
