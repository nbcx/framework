<?php
/*
 * This file is part of the NB Framework package.
 *
 * Copyright (c) 2018 https://nb.cx All rights reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace nb\event;

use nb\Config;
use nb\I18n;
use nb\Router;

/**
 * Framework
 *
 * @package nb\event
 * @link https://nb.cx
 * @since 2.0
 * @author: collin <collin@nb.cx>
 * @date: 2017/12/2
 */
class Framework {

    /**
     * 当Config对象被创建后，将调用此方法
     * 注意，在swoole和fpm两种方式中，每次请求的生命周期不同
     *
     * @param Config $conf
     */
    public function config(Config &$conf) {}

    /**
     * 当访问不存在的控制器或方法时，将回调此方法
     *
     * @param Router $router
     * @throws \Exception
     */
    public function notfound() {
        $router = Router::driver();
        if (Config::$o->sapi == 'cli') {
            die('Cli Not Found:' .  $router->module. '/' .$router->controller . '/' . $router->function."\n");
        }
        if (isset($this->call['notfound']) && !call_user_func($this->call['notfound'], $router)) {
            return;
        }
        if(ob_get_level() > 0) {
            ob_clean();
        }
        //if (!headers_sent()) {
        //    header('HTTP/1.1 404 Not Found');
        //    header('Status:404 Not Found');
        //}
        //if (f('ajax')) {
        //    Config::$o->debug and quit('Ajax Not Found:' . $router->getModel(). '/' . $router->getController() . '/' . $router->getFunction());
        //    quit('404 page not found url!');
        //}
        if (Config::$o->debug) {
            $hint = I18n::t('请求无法应答！');
            $message = I18n::t(
                '请检查下面路由信息是否正确！ %s%s%s%s',
                '<br/>module : '.$router->module,
                '<br/>folder : '.$router->folder,
                '<br/>controller : '.$router->controller,
                '<br/>function : '.$router->function
            );
        }
        include __DIR__ . DS . 'html' . DS . 'hint.tpl.php';
    }


    /**
     * 当程序运行遇到错误，都会回调此方法
     *
     * PS:error里不能使用quit函数，否则会造成错误冲突
     *
     * @param array $e 错误信息
     * @param bool $deadly 是否终止程序运行，一般true代表遇到致命错误
     */
    public function error($e,$deadly = false) {
        return true;
        if (Config::$o->debug) {
            if ($deadly) {
                if (Config::$o->sapi=='cli') {
                    echo "\n:( Have Error\n";
                    echo "CODE: {$e->getCode()} \n";
                    echo "FILE: {$e->getFile()} \n";
                    echo "LINE: {$e->getLine()}\n";
                    echo "DESC: {$e->getMessage()}\n\n";
                }
                else {
                    echo "\n:( Swoole Error\n";
                    echo "CODE: {$e->getCode()} \n";
                    echo "FILE: {$e->getFile()} \n";
                    echo "LINE: {$e->getLine()}\n";
                    echo "DESC: {$e->getMessage()}\n\n";
                    return;
                    if(ob_get_level() > 0) {
                        ob_clean();
                    }
                    include __NB__ . 'templet' . DS . 'exception.tbl.php';
                }
            }
        }
    }

    /**
     * 当数据校验失败，会回调此方法
     * @param $args 错误参数
     * @param $msg  错误信息
     */
    public function validate($args,$msg) {
        tips(I18n::t('数据验证失败！'),$args.'|'.$msg);
    }

    /**
     * 提示
     * @param $hint
     * @param $message
     * @param null $url
     * @param int $wait
     * @throws \Exception
     */
    public function tips($hint,$message,$url=null,$wait=3) {
        include __DIR__ . DS.'html'. DS.'hint.tpl.php';
        quit();
    }

    /**
     * 在路由解析前，会执行此方法，方便自定义一些特殊路由
     * @param Router $router
     */
    public function redirect() {}

    /**
     * 此方法会在debug开始写记录文件时调用。
     * 根据返回结果，判断是否记录此次调试信息，true 记录，false 不记录
     * 此方法多用于请求过多时，过滤出关键记录，
     * 比如只记录请求POST参数id为3的记录等
     * 如果关闭debug，则此回调将不起作用
     * @return bool
     */
    public function debug() {
        return true;
    }

    /**
     * 当路由解析为module访问时,将回调此函数，然后再创建控制器对象
     *
     * @param $module
     * @return bool  返回false时，将作为未启用module处理
     */
    public function module($module) {

        $conf = Config::$o;
        $conf_file = __APP__.$conf->folder_module.DS.$module.DS.'config.inc.php';
        if(is_file($conf_file)) {
            $config = include $conf_file;
            if(isset($config['path_autoinclude'])) {
                $path_autoext = isset($config['path_autoext'])?$config['path_autoext']:$conf->path_autoext;
                $conf->import(
                    $config['path_autoinclude'],
                    $path_autoext
                );
                unset($config['path_autoinclude']);
            }
            if(isset($config['view'])) {
                Config::setx_merge('view',$config['view']);
                unset($config['view']);
            }

            foreach ($config as $k=>$v) {
                $conf->$k = $v;
                //Config::setx($k,$v);
            }
        }
        else {
            //$conf->view['view_path'] = __APP__.$conf->module.DS.$module.DS.'view'.DS;
            $conf->import(
                [__APP__.$conf->folder_module.DS.$module.DS.'include'.DS],
                $conf->path_autoext
            );
        }
        return true;
    }

}