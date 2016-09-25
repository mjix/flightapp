<?php
defined('ROOT_PATH') or define('ROOT_PATH',  dirname(__DIR__));
defined('APP_PATH') or define('APP_PATH', ROOT_PATH.DIRECTORY_SEPARATOR.'app');

$app = require_once APP_PATH.'/bootstrap.php';

$app->start();
