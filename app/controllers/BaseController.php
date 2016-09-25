<?php
namespace app\controllers;

class BaseController{

    protected static function assign($name, $val=''){
        view()->set($name, $val);
    }
}

