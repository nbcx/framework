<?php
/*
 * This file is part of the NB Framework package.
 *
 * Copyright (c) 2018 https://nb.cx All rights reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace nb\cache;

class File {

    private $_path;
    private $_timeout;
    private $_exttension;
    private $_oldtimeout;

    private static $_cache;

    /**
     * 构造函数
     */
    private function __construct() {
        $config = Config::getx('filecache');
        $this->_path = Config::getx('path_temp').'data'.DIRECTORY_SEPARATOR;
        if (!is_dir($this->_path)) {
            if(!mkdir($this->_path,0755,true)){
                throw new \Exception('Create cache dir is fail!');
            }
        }
        $this->_exttension = $config['ext'];
        $this->_oldtimeout = $this->_timeout  = $config['timeout'];
    }

    private static function _cache(){
        if(self::$_cache) {
            return self::$_cache;
        }
        return self::$_cache = new Cache();
    }


    public static function set($key,$value){
        $cache = self::_cache();
        $filename = $cache->_get_cache_file($key);
        return file_put_contents($filename, serialize($value), LOCK_EX); //写文件, 文件锁避免出错
    }

    /**
     * 获取缓存数据
     * $dataObj可以是一个函数或一个普通变量
     * 如果$key对应的缓存不存在时,将执行$dataObj函数(如果$dataObj是函数),并把其返回值作为最新缓存返回
     * 或把$dataObj(如果$dataObj不是函数)值保存为最新的缓存返回
     * @param $key
     * @param null $dataObj
     * @return mixed|null
     */
    public static function getx($key,$timeout,$dataObj=null){
        $cache = self::_cache();
        $cache->_timeout = $timeout;
        $tesult = self::get($key,$dataObj);
        $cache->_timeout = $cache->_oldtimeout;
        return $tesult;
    }

    /**
     * 获取缓存数据
     * $dataObj可以是一个函数或一个普通变量
     * 如果$key对应的缓存不存在时,将执行$dataObj函数(如果$dataObj是函数),并把其返回值作为最新缓存返回
     * 或把$dataObj(如果$dataObj不是函数)值保存为最新的缓存返回
     * @param $key
     * @param null $dataObj
     * @return mixed|null
     */
    public static function get($key,$dataObj=null){
        $cache = self::_cache();
        if ($cache->_has_cache($key)) {
            $filename = $cache->_get_cache_file($key);
            $value=unserialize(file_get_contents($filename));
            if (!empty($value)) {
                return $value;//unserialize($value);
            }
        }
        if(is_object($dataObj)){
            $dataObj = call_user_func($dataObj,$key);
        }
        self::set($key,$dataObj);
        return $dataObj;
    }

    /**
     * 修改存储内容
     * @param $key 修改的key值
     * @param $data 修改的内容
     * @return int
     */
    public static function update($key,$data) {
        $cache = self::_cache();
        if(is_array($data)) {
            $result = $cache->get($key);
            $data = array_merge($result,$data);
        }
        return $cache->set($key,$data);
    }

    /**
     * 存储的$content不会被序列化,一般用来存储html页面
     * 当$content为空时,自动调用ob_get_contents获取数据作为缓存
     * @param $key
     * @param null $content
     */
    public static function save($key,$content=null,$timeout=false){
        $cache = self::_cache();
        $cache->_timeout = $timeout===false?$cache->_oldtimeout:$timeout;
        $filename = $cache->_get_cache_file($key);
        if($content == null){
            $content = ob_get_contents();
            ob_end_clean();
        }
        return file_put_contents($filename, $content, LOCK_EX);
    }

    /**
     * 对应save函数,不对缓存进行反序列化
     * @param $key
     */
    public static function read($key,$content=null,$timeout=false){
        $cache = self::_cache();
        $cache->_timeout = $timeout===false?$cache->_oldtimeout:$timeout;
        if ($cache->_has_cache($key)) {
            $filename = $cache->_get_cache_file($key);
            return file_get_contents($filename);
        }
        if(is_object($content)){
            $content = call_user_func($content,$key);
        }
        self::save($key,$content);
        return $content;
    }

    //删除对应的一个缓存
    public static function delete($key) {
        $cache = self::_cache();
        $cache->_unlink($cache->_get_cache_file($key));
    }

    //删除模糊匹配的缓存文件
    public static function rm($key=null) {
        $cache = self::_cache();
        if($key) {
            $fp = opendir($cache->_path);
            while(!false == ($fn = readdir($fp))) {
                if($fn == '.' || $fn =='..') {
                    continue;
                }
                $cache->_unlink($cache->_path . $fn);
            }
        }
        else {
            $files = glob($cache->_path . $key);
            if($files){
                foreach ($files as $v){
                    $cache->_unlink($v);
                }
            }
        }
    }

    //是否存在缓存
    private function _has_cache($key) {
        $filename = $this->_get_cache_file($key);
        if(file_exists($filename) && (filemtime($filename) + $this->_timeout >= time() || $this->_timeout < 1)) {
            return true;
        }
        return false;
    }

    //拼接缓存路径
    private function _get_cache_file($key) {
        if ($key == null) {
            $key = 'unvalid_cache_key';
        }
        return $this->_path . $key . $this->_exttension;
    }

    /**
     * 按每天定时更新
     * 如设定存活时间为8,那么文件内容会在第二天8点以后更新
     * @param $key
     * @param null $dataObj
     * @return mixed|null
     */
    public function gettimer($key,$dataObj=null) {
        if ($this->_has_cache_time($key)) {
            $filename = $this->_get_cache_file($key);
            $value = file_get_contents($filename);
            if (!empty($value)) {
                return unserialize($value);
            }
        }
        if(is_object($dataObj)){
            $dataObj = call_user_func($dataObj,$key);
            $this->set($key,$dataObj);
        }
        else if($dataObj != null){
            $this->set($key,$dataObj);
        }
        return $dataObj;
    }

    //是否存在缓存
    private function _has_cache_time($key) {
        $filename = $this->_get_cache_file($key);
        if(!file_exists($filename)) {
            return false;
        }
        if(date('Ymd', filemtime($filename)) != date('Ymd') && $this->_timeout > date('H')) {
            return false;
        }
        return true;
    }

    private function _unlink($file){
        if(file_exists($file)) {
            unlink($file);
        }
    }

}