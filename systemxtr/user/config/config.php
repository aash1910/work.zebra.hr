<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$config['enable_devlog_alerts'] = 'y';
$config['index_page'] = 'index.php';
$config['share_analytics'] = 'y';
$config['require_cookie_consent'] = 'n';
$config['strip_image_metadata'] = 'n';
$config['site_license_key'] = '';
$config['speedy_frontedit_check_url'] = 'https://work.zebra.hr/index.php?ACT=63';
$config['speedy_enabled'] = 'no';
$config['speedy_driver'] = 'file';
$config['ignore_entry_stats'] = 'y';
// ExpressionEngine Config Items
// Find more configs and overrides at
// https://docs.expressionengine.com/latest/general/system-configuration-overrides.html

$config['app_version'] = '7.5.16';
$config['encryption_key'] = 'af9b25690ed163609cd64bb325b78f5411ff776b';
$config['session_crypt_key'] = 'c537a501c3cdc9d7088c751e698518bde4474a90';
$config['database'] = array(
	'expressionengine' => array(
		'hostname' => 'localhost',
		'database' => 'workzebra_baza',
		'username' => 'root',
		'password' => 'root',
		'dbprefix' => 'exp_',
		'char_set' => 'utf8mb4',
		'dbcollat' => 'utf8mb4_unicode_ci',
		'port'     => ''
	),
);
$config['show_ee_news'] = 'n';

// Enable logging (1 = errors only, 2 = debug, 3 = info, 4 = all)
$config['log_threshold'] = 1;

// Base path and URL for local development
$config['base_path'] = '/Users/ashraful3/Ash/Sites/work.zebra.hr/';
$config['base_url'] = 'http://127.0.0.1:8000/';

// EOF