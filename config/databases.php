<?php

//local mysql config
if(config('app.env', '') == 'local'){
    $dbconfig = [
        'ymstaff' => [
            'host' => '127.0.0.1',
            'port' => '3306',
            'database' => 'youme_db_ymstaff',
            'username' => 'root',
            'password' => '',
            'charset' => 'utf8'
        ],
    ];
}else{
    $dbconfig = [
        'ymstaff' => [
            'host' => '10.18.30.30',
            'port' => '3306',
            'database' => 'youme_db_ymstaff',
            'username' => 'root',
            'password' => '123',
            'charset' => 'utf8'
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
