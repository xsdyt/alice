<?php

return [

    /*
    |--------------------------------------------------------------------------
    | PDO Fetch Style
    |--------------------------------------------------------------------------
    |
    | By default, database results will be returned as instances of the PHP
    | stdClass object; however, you may desire to retrieve records in an
    | array format for simplicity. Here you can tweak the fetch style.
    |
    */

    'fetch' => PDO::FETCH_CLASS,

    /*
    |--------------------------------------------------------------------------
    | Default Database Connection Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the database connections below you wish
    | to use as your default connection for all database work. Of course
    | you may use many connections at once using the Database library.
    |
    */

    'default' => env('DB_CONNECTION', 'mysql'),

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    |
    | Here are each of the database connections setup for your application.
    | Of course, examples of configuring each database platform that is
    | supported by Laravel is shown below to make development simple.
    |
    |
    | All database work in Laravel is done through the PHP PDO facilities
    | so make sure you have the driver for your particular database of
    | choice installed on your machine before you begin development.
    |
    */

    'connections' => [

        'sqlite' => [
            'driver'   => 'sqlite',
            'database' => storage_path('database.sqlite'),
            'prefix'   => '',
        ],

        'mysql' => [
            'driver'    => 'mysql',
    		'host'      => '49.128.11.45',
        	'port'		=> 3306,
    		'database'  => 'os_pink21_third_account',
    		'username'  => 'os_dbadmin',
    		'password'  => 'OASJpMIB9X0CkeoOJAOH',
            'charset'   => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix'    => '',
            'strict'    => false,
        ],

    	'game' => [
    		'driver'    => 'mysql',
    		'host'      => '47.89.55.26',
    		'port'		=> 3306,
    		'database'  => 'os_pink21_third_account',
    		'username'  => 'root',
    		'password'  => 'iRVfvA+ANXwz',
    		'charset'   => 'utf8',
    		'collation' => 'utf8_unicode_ci',
    		'prefix'    => '',
    		'strict'    => false,
    	],
        
        'imgame' => [
    		'driver'    => 'mysql',
    		'host'      => '47.89.55.26',
    		'port'		=> 3306,
    		'database'  => 'im_21pink_account',
    		'username'  => 'root',
    		'password'  => 'iRVfvA+ANXwz',
    		'charset'   => 'utf8',
    		'collation' => 'utf8_unicode_ci',
    		'prefix'    => '',
    		'strict'    => false,
    	],

    	'log' => [
    		'driver'    => 'mysql',
    		'host'      => '47.89.55.26',
    		'port'		=> 3306,
    		'database'  => 'os_pink21_third_account_log',
    		'username'  => 'root',
    		'password'  => 'iRVfvA+ANXwz',
    		'charset'   => 'utf8',
    		'collation' => 'utf8_unicode_ci',
    		'prefix'    => '',
    		'strict'    => false,
    	], 
        'imlog' => [
    		'driver'    => 'mysql',
    		'host'      => '47.89.55.26',
    		'port'		=> 3306,
    		'database'  => 'im_21pink_account_log',
    		'username'  => 'root',
    		'password'  => 'iRVfvA+ANXwz',
    		'charset'   => 'utf8',
    		'collation' => 'utf8_unicode_ci',
    		'prefix'    => '',
    		'strict'    => false,
    	],
        'config' => [
    		'driver'    => 'mysql',
    		'host'      => '47.89.55.26',
    		'port'		=> 3306,
    		'database'  => 'internal_21pink_config',
    		'username'  => 'root',
    		'password'  => 'iRVfvA+ANXwz',
    		'charset'   => 'utf8',
    		'collation' => 'utf8_unicode_ci',
    		'prefix'    => '',
    		'strict'    => false,
    	],
         'im_config' => [
    		'driver'    => 'mysql',
    		'host'      => '47.89.55.26',
    		'port'		=> 3306,
    		'database'  => 'internal_im_config',
    		'username'  => 'root',
    		'password'  => 'iRVfvA+ANXwz',
    		'charset'   => 'utf8',
    		'collation' => 'utf8_unicode_ci',
    		'prefix'    => '',
    		'strict'    => false,
    	],
//        不用修改
         'formal_config' => [
    		'driver'    => 'mysql',
    		'host'      => '47.88.190.176',
    		'port'		=> 3306,
    		'database'  => '21pink_config',
    		'username'  => 'root',
    		'password'  => '5tgb6yhn',
    		'charset'   => 'utf8',
    		'collation' => 'utf8_unicode_ci',
    		'prefix'    => '',
    		'strict'    => false,
    	],
    		
        'analysis' => [

                'driver'    => 'mysql',
                'host'      => '47.89.55.26',
                'port'      => 3306,
                'database'  => 'pink_analyse',
                'username'  => 'root',
                'password'  => 'iRVfvA+ANXwz',
                'charset'   => 'utf8',
                'collation' => 'utf8_unicode_ci',
                'prefix'    => '',
                'strict'    => false,
        ],


    		
        'pgsql' => [
            'driver'   => 'pgsql',
            'host'     => env('DB_HOST', 'localhost'),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'charset'  => 'utf8',
            'prefix'   => '',
            'schema'   => 'public',
        ],

        'sqlsrv' => [
            'driver'   => 'sqlsrv',
            'host'     => env('DB_HOST', 'localhost'),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'charset'  => 'utf8',
            'prefix'   => '',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Repository Table
    |--------------------------------------------------------------------------
    |
    | This table keeps track of all the migrations that have already run for
    | your application. Using this information, we can determine which of
    | the migrations on disk haven't actually been run in the database.
    |
    */

    'migrations' => 'migrations',

    /*
    |--------------------------------------------------------------------------
    | Redis Databases
    |--------------------------------------------------------------------------
    |
    | Redis is an open source, fast, and advanced key-value store that also
    | provides a richer set of commands than a typical key-value systems
    | such as APC or Memcached. Laravel makes it easy to dig right in.
    |
    */

    'redis' => [

        'cluster' => false,

        'default' => [
            'host'     => '10.45.247.125',
            'port'     => 6379,
            'database' => 0,
        ],

    ],

];
