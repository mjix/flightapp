<?php
namespace exts;

class Config{
    
    //store config
    protected static $_config = [];

    /**
     * try to load config file
     * @param  string $key config key
     * @return mixed      config value
     */
    protected static function _loadConfig($key, $dval=null){
        //split key: app.debug //config/app.php
        $keys = explode('.', $key);
        $nkey = $keys[0];

        //if config.file loaded but not found config
        if(isset(self::$_config[$nkey])){
            return $dval;
        }

        $file = ROOT_PATH.'/config/'.$nkey.'.php';
        if(file_exists($file)){

            $config = require_once $file;
            if(is_array($config)){
                self::set($config, null, $nkey.'.', false);
            }
            
            self::set($nkey, $config, '', false);
            if(isset(self::$_config[$key])){
                return self::$_config[$key];
            }
        }
        return $dval;
    }

    /**
     * get config
     * @param  string $key config key
     * @return mixed      config value
     */
    public static function get($key='', $dval=null){
        if(!$key){
            return self::$_config;
        }
        if(isset(self::$_config[$key])){
            return self::$_config[$key];
        }

        return self::_loadConfig($key, $dval);
    }

    /**
     * set config
     * @param string $key    key
     * @param mixed $value  value
     * @param string $prefix prefix key
     */
    public static function set($key, $value='', $prefix='', $replace=true){
        if(is_array($key)){
            foreach ($key as $kk => $vv) {
                if(!$replace && isset(self::$_config[$prefix.$kk])){
                    continue;
                }
                self::$_config[$prefix.$kk] = $vv;
            }
            return true;
        }else{
            if(!$replace && isset(self::$_config[$prefix.$key])){
                return self::$_config[$prefix.$key];
            }
            return self::$_config[$prefix.$key] = $value;
        }
    }
}

