<?php

use ExpressionEngine\Service\Database\Database;
use ExpressionEngine\Service\Database\DBConfig;

return array(
      'author'      => 'Percipio',
      'author_url'  => 'http://brandnewbox.co.uk/',
      'name'        => 'Data_import',
      'description' => 'Data import from external databases',
      'version'     => '3.0.0',
      'namespace'   => 'Data_import',
      'settings_exist' => TRUE,

      'services' => array(

	    // This service will be used to query our external database
	    // e.g., ee('data_import:db')->select()
	    'db' => function($addon)
	    {
	      return $addon->make('data_import:Database')->newQuery();
	    },

	    // This service manages our external database connection
	    // e.g., ee('data_import:Database')->getLog()
	    'Database' => function($addon)
	    {
	      // Makes sure we only do this work once per page request
	      static $db;

	      if (empty($db))
	      {
	        // fetch config from system/user/config/data_import_database.php
	        $config = ee('Config')->getFile('data_import_database');

	        // create the DBConfig object
	        $db_config = new DBConfig($config);

	        // select the database connection group
	        $db_config->getGroupConfig('data_import');

	        // connect to and make the Database object
	        $db = new Database($db_config);
	      }

	      return $db;
	    }

	  )

);