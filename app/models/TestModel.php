<?php
namespace app\models;

class TestModel{

    public static function get_news(){
        $table = db('dbname')->table('news');
        $newsList = $table->paginate();

        return $newsList;
    }

}

