<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require_once PATH_THIRD . 'minimee/config.php';

return array(
    'name'           => MINIMEE_NAME,
    'version'        => MINIMEE_VER,
    'author'         => MINIMEE_AUTHOR,
    'author_url'     => MINIMEE_DOCS,
    'description'    => MINIMEE_DESC,
    'namespace'      => 'Solspace\\Minimee',
    'docs_url'       => MINIMEE_DOCS,
    'settings_exist' => true
);