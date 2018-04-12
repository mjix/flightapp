<?php

//local mysql config
if(config('app.env', '') == 'local'){
    $dbconfig = [
        'default' => [
            'driver' => 'mysql',
            'host' => '127.0.0.1',
            'port' => '3306',
            'database' => 'db_test',
            'username' => 'root',
            'password' => '123',
            'charset' => 'utf8',
            'collation' => 'utf8_general_ci'
        ],
    ];
}else{
    $dbconfig = [
        'default' => [
            'driver' => 'mysql',
            'host' => '10.10.10.10',
            'port' => '3306',
            'database' => 'db_test',
            'username' => 'root',
            'password' => '123',
            'charset' => 'utf8',
            'collation' => 'utf8_general_ci'
        ],
    ];
}

return [
    /*
    |--------------------------------------------------------------------------
    | Default Database Connection Name
    |--------------------------------------------------------------------------
    */
    'default' => 'mysql',

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    */
    'connections' => $dbconfig,
];
