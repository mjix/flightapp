<?php
use app\controllers\HomeController;

$app = app();
$app->route('/', [HomeController::class, 'index']);
