<?php
/*
 * This file is part of the NB Framework package.
 *
 * Copyright (c) 2018 https://nb.cx All rights reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace nb\exception;


use nb\Config;
use nb\Debug;
use nb\Pool;

/**
 * Swoole
 *
 * @package nb\exception
 * @link https://nb.cx
 * @since 2.0
 * @author: collin <collin@nb.cx>
 * @date: 2017/12/7
 */
class Swoole extends Native {

    /**
     * 当Swoole server关闭时
     * 当Task遇到致命错误时
     *
     * @throws \ReflectionException
     */
    public function shutdown() {
        l('shutdown','err');
        //!\nb\Server::$o ||
        if(Debug::driver()->synchronous() && !Pool::get('\swoole\http\Request')) {
            return;
        }
        //有错记错
        $e = error_get_last();
        if($e) {
            l('shutdown:'.$e['message'],'err');
            $e = new \ErrorException($e['message'], $e['type'], $e['type'], $e['file'], $e['line']);
            //是否为同步进程,非同步，即为task进程
            if(Debug::driver()->synchronous()) {
                $this->dowith($e,true);
                Pool::get('\swoole\http\Response')->end(ob_get_contents());
                ob_end_clean();
            }
            else {
                $this->dowith($e);
            }
        }
        Debug::end();
        Pool::destroy();
    }
    /*
    protected function show($e,$deadly = false) {
        if (Config::$o->debug && $deadly) {
            if(ob_get_level() > 0) {
                ob_clean();
            }
            include __NB__ . 'templet' . DS . 'exception.tbl.php';
        }
    }
    */


}