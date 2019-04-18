<?php
/*
 * This file is part of the NB Framework package.
 *
 * Copyright (c) 2018 https://nb.cx All rights reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace nb\request;

use nb\Access;
use nb\Pool;

/**
 * Driver
 *
 * @package nb\request
 * @link https://nb.cx
 * @since 2.0
 * @author: collin <collin@nb.cx>
 * @date: 2017/11/28
 */
abstract class Driver extends Access {

    /*
    protected $get;
    protected $post;
    protected $request;
    protected $cookie;
    protected $files;
    protected $server;
    protected $input;
    */


    /**
     * 获取表单数据，返回一个结果数组
     * @param string $method
     * @param null $args
     * @return array
     */
    abstract public function form($method='request',array $args=null);

    /**
     * 获取表单参数对应的值
     * 如果获取多个，则以值数组的形式返回
     *
     * @param $arg
     * @param array ...$args
     * @return array|mixed|null
     */
    abstract public function input($arg,...$args);

}