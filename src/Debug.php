<?php
/*
 * This file is part of the NB Framework package.
 *
 * Copyright (c) 2018 https://nb.cx All rights reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace nb;

/**
 * 调式
 *
 * @package nb
 * @link https://nb.cx
 * @since 2.0
 * @author: collin <collin@nb.cx>
 * @date: 2017/3/30
 */
class Debug extends Component {

	/**
	 * @return Debug
	 */
	public static function start($synchronous = true){
	    if(!Config::$o->debug) {
	        return false;
        }
        self::driver()->start($synchronous);
	}


    /**
     * @param $e
     * @param bool $deadly
     * @throws \ReflectionException
     */
    public static function exception($e,$deadly=false) {
        self::record(2,$e);
        Pool::object('nb\\event\\Framework')->error($e,$deadly);
    }

	/**
	 *
	 * @param $type
	 * @param $key
	 * @param $val
	 */
	public static function record($type,$parama,$paramb=null){
        if(!Config::$o->debug) {
            return false;
        }
        self::driver()->record($type,$parama,$paramb);
	}

    /**
     *
     * @param $type
     * @param $key
     * @param $val
     */
    public static function breakd($name,$value=null){
        if(!Config::$o->debug) {
            return false;
        }
        self::driver()->record(1,$name,$value);
    }

    /**
     * 代替原生的die函数,方便程序在不同的环境下运行
     * @param null $msg
     * @throws Exception
     */
    public static function die($msg=null) {
        self::driver()->die($msg);
    }

    /**
     * 将错误对象转为数组
     * @param $e
     */
    public static function e2Array($e) {
        is_object($e) and $e = [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
            'type' => $e->getCode()
        ];
        return $e;
    }

	/**
	 * 统计信息，存入Bug
	 */
	public static function end(){
        if(!Config::$o->debug) {
            return false;
        }
        self::driver()->end();
	}

    /**
     * 格式打印
     * @param 不定变量
     */
    public static function e() {
        if(!Config::$o->debug) return;
        $args = func_get_args();
        if (!headers_sent()) {
            header("Content-type: text/html; charset=utf-8");
        }
        foreach($args as $arg) {
            self::ex($arg,true);
        }
    }

    /**
     * 浏览器友好的变量输出
     * @access public
     * @param  mixed         $var 变量
     * @param  boolean       $detailed 是否详细输出 默认为true 如果为false 则使用print_r输出
     * @param  string        $label 标签 默认为空
     * @param  integer       $flags htmlspecialchars flags
     * @return void|string
     */
    public static function ex($var, $detailed = false, $label = null, $flags = ENT_SUBSTITUTE) {
        if(!Config::$o->debug) return;
        $label = (null === $label) ? '' : rtrim($label) . ':';

        if (is_object($var)) { //$var instanceof \nb\Collection
            $detailed = false;
        }
        ob_start();
        $detailed?var_dump($var):print_r($var);
        $output = ob_get_clean();
        $output = preg_replace('/\]\=\>\n(\s+)/m', '] => ', $output);

        if (Config::$o->sapi == 'cli') {
            $output = PHP_EOL . $label . $output . PHP_EOL;
        }
        else {
            if (!extension_loaded('xdebug')) {
                $output = htmlspecialchars($output, $flags);
            }
            $output = '<pre>' . $label . $output . '</pre>';
        }
        echo $output;
    }

    /**
     * 格式打印并结束运行
     */
    public static function ed() {
        if(!Config::$o->debug) return;
        $args = func_get_args();
        $args and call_user_func_array('\nb\Debug::e',$args);
        quit(0);
    }

    /**
     * 格式打印加强版
     * 同时输出函数调用路径信息
     */
    public static function ee($var) {
        if(!Config::$o->debug) return;
        $br = '<pre>';
        //$hr = '<hr>';
        if (Config::$o->sapi=='cli') {
            $br = "\n";
            //$hr = "\n----------------------\n";
        }
        echo $br;
        debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        self::ex($var,true,'ee');
        echo $br;
    }

    /**
     * 记录信息到日志文件
     * 底层是通过error_log函数
     * @param unknown $data
     * @param string $fileName
     */
    public static function log($data, $fileName = 'log', $ext='txt', $format='Ymd') {
        $filePath = Config::getx('path_temp') . 'logs'. DIRECTORY_SEPARATOR;
        if (!is_dir($filePath)) {
            if(!mkdir($filePath,0755,true)){
                throw new \Exception('Create log dir is fail!');
            }
        }
        $message = '[' . date("Y-m-d h:i:s") . ']  ';
        if($data instanceof \Throwable) {
            $message .= $data->getMessage().' in '.$data->getFile().':'.$data->getLine();
        }
        else {
            $message .= (is_array($data) ? print_r($data, true) : $data);
        }
        $message .="\n";
        $format = date($format);
        $fileName = "{$filePath}{$fileName}_{$format}.{$ext}";
        error_log($message, 3, $fileName);
    }


}