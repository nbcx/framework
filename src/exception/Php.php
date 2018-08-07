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

/**
 * 处理php-fpm模式下的异常
 *
 * @package nb\exception
 * @link https://nb.cx
 * @since 2.0
 * @author: collin <collin@nb.cx>
 * @date: 2017/12/7
 */
class Php extends Driver {

    protected function show($e,$deadly = false) {
        if (Config::$o->debug && $deadly) {
            if(ob_get_level() > 0) {
                $obget = ob_get_contents();
                ob_clean();
            }
            include __DIR__ . DS . 'html' . DS . 'exception.tpl.php';
        }
    }

}