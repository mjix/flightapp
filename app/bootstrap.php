<?php
ini_set('date.timezone','Asia/Shanghai');
spl_autoload_register(function($class){
    $file = ROOT_PATH.'/'.str_replace('\\', '/', $class).'.php';
    if(file_exists($file)){
        return require $file;
    }
});

require ROOT_PATH.'/vendor/autoload.php';
require ROOT_PATH.'/exts/facades.php';
require APP_PATH.'/routes.php';

$app = app();

//set template path
$app->set('flight.views.path', APP_PATH.'/views');

//register log handler; app()->log()->info('setest');
$app->register('log', exts\LogHandler::class);

//init ERROR
app()->map('error', function(Exception $ex){
    $error = '<h2>'.$ex->getMessage().'</h2>';
    $error .= nl2br($ex->getTraceAsString());
    app()->log()->error($error);
});

//init DB
exts\DB::init('databases.connections');

return $app;
