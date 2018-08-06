<?php
/*
 * This file is part of the NB Framework package.
 *
 * Copyright (c) 2018 https://nb.cx All rights reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace nb\router;

use nb\Access;
use nb\Config;

/**
 * Driver
 *
 * @package nb\router
 * @link https://nb.cx
 * @since 2.0
 * @author: collin <collin@nb.cx>
 * @date: 2017/11/29
 */
abstract class Driver extends Access {

    /**
     * 调度器在戳发redirect事件后，将调用此函数
     * 此函数应该保证控制器是有值的
     */
    abstract public function mustAnalyse();

    /**
     * 返回控制器的默认目录
     * @return mixed
     */
    protected function _folder_default(){
        return Config::$o->folder_controller;
    }

    /**
     * 获取路由指向的控制器函数
     * @return string
     */
    public function _function() {
        return Config::$o->default_func;
    }

    /**
     * 获取控制器完整类，并自动加载
     * @return bool|string
     */
    protected function _class() {
        $conf = Config::$o;
        $folder_controller = $this->folder_default;

        $path = __APP__;
        if($this->module) {
            $path .= $conf->folder_module.'/'.$this->module.'/';
            $class = $conf->folder_module.'\\'.$this->module.'\\';
        }
        else {
            $path .=($conf->folder_app?$conf->folder_app.'/':'');
            $class = '';
        }
        if($this->folder) {
            //folder\filename
            $tmp = $path .$this->folder.'/';
            if (!is_dir($tmp) && $folder_controller) {
                //controller\folder\filename
                $tmp = $path.$folder_controller.'/'.$this->folder.'/';
            }
            $path  = $tmp;
            $class .= "controller\\{$this->folder}\\{$this->controller}";
        }
        else {
            $path  .= $folder_controller.'/';
            $class .=  "controller\\{$this->controller}";
        }
        $file = $path.$this->controller.'.php';
        $auto = glob($path.'*.php');
        foreach ($auto as $v) {
            if (strcasecmp($v, $file) == 0) {
                include_once $v;
                return class_exists($class)?$class:false;
            }
        }
        return false;
    }

    /**
     * read
     *
     * @var module
     * @var controller
     * @var function
     * @var namespace
     * @var folder
     */

    /*
    protected $class;

    abstract public function url($name, array $value = NULL, $prefix = NULL);

    abstract public function setModule($module);

    abstract public function setController($controller);

    abstract public function setFunction($function);

    abstract public function setClass($class);

    abstract public function ___module();

    abstract public function ___controller();

    abstract public function ___function();

    abstract public function ___namespace();

    abstract protected function ___class();

    abstract public function match($pathInfo, $parameter = NULL);
    */

    protected function load() {
        $folder_controller = $this->folder_default;
        $conf = Config::$o;
        //$controller = $type=='command'?$conf->folder_console:$conf->folder_controller;

        $path = __APP__;
        if($this->module) {
            $path .= $conf->folder_module.'/'.$this->module.'/';
        }
        else {
            $app  = $conf->folder_app?$conf->folder_app.'/':'';
            $path .=$app;
        }
        if($this->folder) {
            //folder\filename
            $tmp = $path .$this->folder.'/';
            if (!is_dir($tmp) && $folder_controller) {
                //controller\folder\filename
                $tmp = $path.$folder_controller.'/'.$this->folder.'/';
            }
            $path = $tmp;
        }
        else {
            $path .= $folder_controller.'/';
        }
        $file = $path.$this->controller.'.php';
        $auto = glob($path.'*.php');

        foreach ($auto as $v) {
            if (strcasecmp($v, $file) == 0) {
                include_once $v;
                return true;
            }
        }
        return false;


        switch ($count) {
            case 2:
                /**
                 * [0] => string(10) "controller"
                 * [1] => string(4) "main"
                 *
                 * controller\filename
                 */
                $path = $app.$controller;
                $file = $path.$ex[1].'.php';
                break;
            case 3:
                /**
                 * [0] => string(10) "controller"
                 * [1] => string(3) "api"
                 * [2] => string(4) "test"
                 */
                /**
                 * folder\filename
                 */
                $path = "{$app}{$ex[1]}/";
                $file = "{$path}{$ex[2]}.php";
                if (!is_dir(__APP__.$path)) {
                    /**
                     * controller\folder\filename
                     */
                    $path = "{$app}{$controller}{$ex[1]}/";
                    $file = "{$path}{$ex[2]}.php";
                }
                break;
            case 4:
                /**
                 * [0] => string(6) "module"
                 * [1] => string(6) "member"
                 * [2] => string(10) "controller"
                 * [3] => string(5) "Index"
                 *
                 * module\modulename\controller\filename
                 */
                $path = "{$conf->folder_module}/{$ex[1]}/".$controller;
                $file = "{$path}{$ex[3]}.php";
                break;
            case 5:
                /**
                 * [0] => string(6) "module"
                 * [1] => string(6) "member"
                 * [2] => string(10) "controller"
                 * [3] => string(5) "admin"
                 * [4] => string(5) "Index"
                 */
                /**
                 * module\modulename\folder\filename
                 */
                $path = "{$conf->folder_module}/{$ex[1]}/{$ex[3]}/";
                $file = "{$path}{$ex[4]}.php";
                if (!is_dir($path)) {
                    /**
                     * module\modulename\controller\folder\filename
                     */
                    $path = "{$conf->folder_module}/{$ex[1]}/{$ex[2]}/{$ex[3]}/";
                    $file = "{$path}{$ex[4]}.php";
                }

                break;
        }

        $path = __APP__.$path;
        $auto = glob($path.'*.php');
        $file = __APP__.$file;

        foreach ($auto as $v) {
            if (strcasecmp($v, $file) == 0) {
                include_once $v;
                return true;
            }
        }
        return false;
    }

    /**
     * 加载控制器
     */
    protected function load_bak($type,$object) {
        $ex = explode('\\',$object);
        $count = count($ex);

        $conf = Config::$o;
        $controller = $type=='command'?$conf->folder_console:$conf->folder_controller;


        //$controller = $this->controller?$this->controller.'/':'';
        $app  = $conf->folder_app?$conf->folder_app.'/':'';


        switch ($count) {
            case 2:
                /**
                 * [0] => string(10) "controller"
                 * [1] => string(4) "main"
                 *
                 * controller\filename
                 */
                $path = $app.$controller;
                $file = $path.$ex[1].'.php';
                break;
            case 3:
                /**
                 * [0] => string(10) "controller"
                 * [1] => string(3) "api"
                 * [2] => string(4) "test"
                 */
                /**
                 * folder\filename
                 */
                $path = "{$app}{$ex[1]}/";
                $file = "{$path}{$ex[2]}.php";
                if (!is_dir(__APP__.$path)) {
                    /**
                     * controller\folder\filename
                     */
                    $path = "{$app}{$controller}{$ex[1]}/";
                    $file = "{$path}{$ex[2]}.php";
                }
                break;
            case 4:
                /**
                 * [0] => string(6) "module"
                 * [1] => string(6) "member"
                 * [2] => string(10) "controller"
                 * [3] => string(5) "Index"
                 *
                 * module\modulename\controller\filename
                 */
                $path = "{$conf->folder_module}/{$ex[1]}/".$controller;
                $file = "{$path}{$ex[3]}.php";
                break;
            case 5:
                /**
                 * [0] => string(6) "module"
                 * [1] => string(6) "member"
                 * [2] => string(10) "controller"
                 * [3] => string(5) "admin"
                 * [4] => string(5) "Index"
                 */
                /**
                 * module\modulename\folder\filename
                 */
                $path = "{$conf->folder_module}/{$ex[1]}/{$ex[3]}/";
                $file = "{$path}{$ex[4]}.php";
                if (!is_dir($path)) {
                    /**
                     * module\modulename\controller\folder\filename
                     */
                    $path = "{$conf->folder_module}/{$ex[1]}/{$ex[2]}/{$ex[3]}/";
                    $file = "{$path}{$ex[4]}.php";
                }

                break;
        }

        $path = __APP__.$path;
        $auto = glob($path.'*.php');
        $file = __APP__.$file;

        foreach ($auto as $v) {
            if (strcasecmp($v, $file) == 0) {
                include_once $v;
                return true;
            }
        }
        return false;
    }


}