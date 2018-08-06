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
 * Request
 *
 * @package nb
 * @link https://nb.cx
 * @since 2.0
 * @author: collin <collin@nb.cx>
 * @date: 2017/12/3
 */
class Request extends Component {

    /**
     * @var \nb\request\Driver
     */
    public static function ins() {
        return self::driver();
    }

    public static function config() {
        if(isset(Config::$o->request)) {
            return Config::$o->request;
        }
        return null;
    }

    /**
     * 获取请求数据
     *
     * @param string $method
     * @param array|null $args
     * @return mixed
     */
    public static function form($method='request',array $args=null) {
        return self::driver()->form($method,$args);
    }

    /**
     * 获取请求数据
     *
     * @param string $method
     * @param array|null $args
     * @return mixed
     */
    public static function formx($method='request',array $args=null) {
        return new Collection(self::form($method,$args));
    }

    /**
     * 获取表单参数对应的值
     * 如果获取多个，则以值数组的形式返回
     *
     * @param $arg
     * @param array ...$args
     * @return array|mixed|null
     */
    public static function input($arg,...$args){
        /** $args != null */
        if($args) {
            if(is_array($args[0])) {
                //$this->input('get',['name','pass']);
                $args = $args[0];
                $method = $arg;
            }
            else {
                //$this->input('name','pass');
                array_unshift($args,$arg);
                $method = 'request';
            }
        }
        else {
            /** $args == null */
            //$this->input('name');
            //$this->input(['name','pass']);
            $args = [$arg];
            $method = 'request';
        }

        $input = self::form($method,$args);

        if(is_array($input) === false) {
            return null;
        }

        if(count($input) == 1) {
            return current($input);
        }

        return array_values($input);
    }

}