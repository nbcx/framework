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
    public function form($method='request',array $args=null) {
        $method = $method === 'auto'?strtolower($this->method()):$method;
        $input = [];
        switch ($method) {
            case 'request':
                $input = $this->request;
                break;
            case 'post':
                $input = $this->post;
                break;
            case 'get':
                $input = $this->get;
                break;
            case 'request':
                $input = $this->request;
                break;
            case 'input':
                $input = $this->input;
                break;
            case 'put':
                parse_str($this->input, $input);
                break;
            case 'files':
                $input = $this->files;
                break;
            case 'server':
                $input = $this->server;
                break;
        }
        if($args) {
            $_input = [];
            foreach ($args as $arg) {
                $_input[$arg] = isset($input[$arg])?$input[$arg]:null;
            }
            $input = $_input;
        }
        return $input;
    }

    /**
     * 获取url的扩展名
     * @return null
     */
    protected function _ext() {
        $url = $this->uri;
        $urlinfo =  parse_url($url);
        $file = basename($urlinfo['path']);
        if(strpos($file,'.') !== false) {
            $ext = explode('.',$file);
            return $ext[count($ext)-1];
        }
        return null;
    }

}