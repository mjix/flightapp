<?php
namespace app\controllers;

class HomeController extends BaseController{

    public function index(){
        //$table = db('ymstaff')->table('roles');
        //var_dump(json_encode($table->get()));
        //
        $val = input('post_key', '');
        $name = strtoupper(query('name', ''));

        //set config
        config(['navigation.current'=>'index']);

        view('home/index', ['name'=>$name]);
    }

}
