<?php
namespace exts;
use Exception;

class LogHandler{
    protected $LOG_LEVEL_DEBUG      =  'DEBUG';
    protected $LOG_LEVEL_INFO       =  'INFO';
    protected $LOG_LEVEL_ERROR      =  'ERROR';
    protected $LOG_LEVELS           =  array('DEBUG'=>1, 'INFO'=>2, 'ERROR'=>3);
    
    protected $_log_format  = '[%s][%s]:%s [%s]';    //当前日志格式
    protected $_config  = [];
    protected $_content = '';                        //日志内容
    
    public function __construct($_config=[]){
        //日志配置
        $this->_config = [
            'logfile' => ROOT_PATH.'/var/log/app-{date}.log',
            'loglevel' => 'info'
        ];
        $config = config('app.logconfig', []);
        $this->_config = array_merge($this->_config, $config, $_config);
        $self = $this;

        $app = app();
        $app->before('stop', function(&$params, &$output) use ($self){
            $self->writer();
        });

        $app->before('error', function(&$params, &$output) use ($self){
            $this->writer();
        });
    }

    protected function getConfig($key, $dval=''){
        return isset($this->_config[$key]) ? $this->_config[$key] : $dval;
    }
    
    public function log($message, $level){
        $_level = strtoupper($this->getConfig('loglevel', 'info'));

        if($this->LOG_LEVELS[$_level] > $this->LOG_LEVELS[$level]){
            return ;
        }

        $log_max_length = $this->getConfig('loglength', 2048);
        if($log_max_length>0 && $log_max_length<strlen($message)){
            $message = substr($message, 0, $log_max_length);
        }

        $message = sprintf($this->_log_format.PHP_EOL, date('Y-m-d H:i:s'), $level, $message, url());
        $this->_content .= $message;
    }

    protected function _format($message, $args){
        if(count($args)>1){
            $args = is_array($args[1]) ? $args[1] : array_slice($args, 1);
            $message = vsprintf($message, $args);
        }
        return $message;
    }

    public function debug($message){
        $msg = $this->_format($message, func_get_args());
        $this->log($msg, $this->LOG_LEVEL_DEBUG);
    }
    
    public function info($message){
        $args = func_get_args();
        $msg = $this->_format($message, $args);
        $this->log($msg, $this->LOG_LEVEL_INFO);
    }

    public function error($message){
        $msg = $this->_format($message, func_get_args());
        $this->log($msg, $this->LOG_LEVEL_ERROR);
    }
    
    public function writer(){
        $file = $this->getConfig('logfile');
        if(!$file){
            return false;
        }
        $file = str_replace('{date}', date('Y-m-d'), $file);
        if($this->_content == ''){
            return true;
        }

        $f = fopen($file, 'a', false);
        fwrite($f, $this->_content);
        if(is_resource($f)){
            fclose($f);
        }
        return true;
    }
}
