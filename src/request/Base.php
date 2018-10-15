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

use nb\Pool;

/**
 * Native
 *
 * @package nb\request
 * @link https://nb.cx
 * @author: collin <collin@nb.cx>
 * @date: 2017/11/28
 */
class Base extends Driver {

    public function __construct($fd=null, $reactor_id=null, $data=null) {
        $this->data = $data;
        $this->fd = $fd;
        $this->reactor_id = $reactor_id;
        Pool::object('\event\Framework')->parser($this);
    }


    /**
     * 获取表单数据，返回一个结果数组
     * @param string $method
     * @param null $args
     * @return array
     */
    public function form($method='request',array $args=null) {
        return $this->data;
    }




}