<?php
use exts\DB;
use flight\util\Collection;

/**
 * get config from config file; eg:config('app.env')
 * @param  string $key eg:app.debug; notice:if array set config
 * @param  mixed $defaultval default val if config not exist
 * @return mixed      value
 */
function config($key, $defaultval=null){
    if(is_array($key)){
        return exts\Config::set($key);
    }else{
        return exts\Config::get($key);
    }
}

/**
 * get flight app
 * @return Engine flight instance
 */
function app(){
    return Flight::app();
}

/**
 * convert to json string
 * @param  array $data data
 * @return string       json
 */
function json($data){
    echo json_encode($data);
}

/**
 * https://github.com/mikecao/flight#requests
 * @return object request object
 */
function request(){
    $req = app()->request();
    if(!isset($req->all)){
        $req->all = new Collection($_REQUEST);
    }
    return $req;
}

/**
 * resource_path
 * @param  string $path eg: include resource_path('/view/home/index.php');
 * @return [type]       [description]
 */
function resource_path($path){
    return APP_PATH.$path;
}

/**
 * render template
 * @param  string $file template path; eg:home/index
 * @param  array  $data assign data to template
 * @return mixed       null
 */
function view($file='', $data=[]){
    if(!$file){
        return app()->view();
    }
    
    return app()->render($file, $data);
}

/**
 * get url
 * @param  string $url base url; eg:task/list
 * @return string      full url
 */
function url($url=''){
    $req = app()->request();
    if(!$url){
        return $req->url;
    }
    $base = $req->base;
    $base = rtrim($base, '/');
    $url = ltrim($url, '/');
    return $base.'/'.$url;
}

/**
 * get request data
 * @param  string $key  input key
 * @param  mixed $dval default value
 * @return mixed       input value
 */
function all($key, $dval=null){
    $all = request()->all;
    return isset($all[$key]) ? $all[$key] : $dval;
}

/**
 * get post form data
 * @param  string $key  form key
 * @param  mixed $dval default value
 * @return mixed       form value
 */
function input($key, $dval=null){
    $data = request()->data;
    return isset($data[$key]) ? $data[$key] : $dval;
}

/**
 * get query parameters
 * @param  string $key  query key
 * @param  mixed $dval default value
 * @return string       query value
 */
function query($key, $dval=null){
    $data = request()->query;
    return isset($data[$key]) ? $data[$key] : $dval;
}

/**
 * get db connection
 * @param  string $key config in config/databases.php
 * @return object      VoodOrm; https://github.com/mardix/VoodOrm
 */
function db($key){
    return DB::connection($key);
}

/**
 * get origin url; eg:http[s]://www.domain.com
 * @return string url
 */
function url_origin(){
    $s        = $_SERVER;
    $port     = $s['SERVER_PORT'];

    $ssl      = (!empty($s['HTTPS']) && $s['HTTPS'] == 'on');
    $sp       = strtolower($s['SERVER_PROTOCOL']);
    $protocol = substr($sp, 0, strpos($sp, '/')).($ssl ? 's' : '');
    $port     = ((!$ssl && $port=='80') || ($ssl && $port=='443')) ? '' : ':'.$port;
    $host     = $s['SERVER_NAME'].$port;
    return $protocol.'://'.$host;
}
