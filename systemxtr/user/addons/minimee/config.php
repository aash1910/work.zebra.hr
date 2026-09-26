<?php

if (!defined('MINIMEE_VER')) {
    define('MINIMEE_NAME', 'Minimee');
    define('MINIMEE_VER', '3.0.0');
    define('MINIMEE_AUTHOR', 'John D Wells');
    define('MINIMEE_DOCS', 'https://johndwells.github.io/Minimee');
    define('MINIMEE_DESC', 'Minimee: minimize & combine your CSS and JS files. Minify your HTML.');
}

$config['name'] = MINIMEE_NAME;
$config['version'] = MINIMEE_VER;

/*
|--------------------------------------------------------------------------
| Legacy NSM Addon Updater
|--------------------------------------------------------------------------
|
| This service no longer exists and is not used by EE7.
| Keeping it disabled avoids unnecessary HTTP requests.
|
*/

$config['nsm_addon_updater']['versions_xml'] = '';