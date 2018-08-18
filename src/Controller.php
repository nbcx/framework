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

use nb\view\Driver;

/**
 * 控制器基类
 *
 * @package nb
 * @link https://nb.cx
 * @since 2.0
 * @author: collin <collin@nb.cx>
 * @date: 2018/7/25
 */
class Controller {

    /**
     * 获取表单参数的类型，request|post|get等等
     * @var string
     */
    public $_method='request';

    /**
     * @var Driver
     */
    protected $view;

    public function __construct($config=[]) {
        $this->view = View::ins();//new Template($config);
    }

    protected function assign($name, $value = ''){
        $this->view->assign($name,$value);
        return $this;
    }

    protected function display($template='', $vars = [], $config = []) {
        $this->view->display($template, $vars, $config);
    }

    /**
     * 获取表单参数,并包装城Collection返回
     * 如果获取多个，则以值数组的形式返回
     *
     * @param mixed ...$params
     * @return Collection
     */
    public function formx(...$params){
        $input = call_user_func_array(
            [$this,'form'],
            $params
        );
        return new Collection($input);
    }

    /**
     * 获取表单参数
     * @param $params
     * @return array|bool
     */
    public function form($method='request', ...$args){

        if(is_array($method)) {
            $args = $method;
            $method = $this->_method;
            //$this->form(['name','pass']);
        }
        else if($args && is_array($args[0])) {
            $args = $args[0];
            //$this->form('get',['name','pass']);
        }

        //$this->form('get','name','pass');
        $method === null and $method = $this->_method;

        $form = Request::form($method,$args);

        $va = Pool::get(Validate::class);
        if(!$va) {
            return $form;
        }

        if($va->scene('_form_',$args)->check($form)) {
            return $form;
        }
        return $this->__error($va->error, $va->field);
    }

    /**
     * 获取表单参数对应的值
     * 如果获取多个，则以值数组的形式返回
     * @param $params
     * @return array|bool
     */
    public function input($arg,...$args){
        /** $args != null */
        if($args) {
            if(is_array($args[0])) {
                //$this->input('get',['name','pass']);
                $args = [$arg,$args[0]];
            }
            else {
                //$this->input('name','pass');
                array_unshift($args,$arg);
                $args = [$this->_method,$args];
            }
        }
        else {
            /** $args == null */
            //$this->input('name');
            //$this->input(['name','pass']);
            $args = [$this->_method,$arg];
        }

        $input = call_user_func_array([$this,'form'],$args);

        if(is_array($input) === false) {
            return null;
        }

        if(count($input) == 1) {
            return current($input);
        }

        return array_values($input);
    }

    /**
     * 事件条件触发
     *
     * @param boolean $condition 触发条件
     * @return $this
     */
    protected function on($condition) {
        if ($condition) {
            return $this;
        }
        else {
            return new Collection();
        }
    }

    /**
     * 中间件条件触发
     *
     * @param bool $condition 是否触发中间件
     * @param null $function  触发中间件的方法
     * @param mixed ...$params 传给中间件函数的参数，最后一个参数如果是function，则为成功后的回调
     * @return Middle|Collection
     * @throws \ReflectionException
     */
    protected function middle($condition=true,$function=null,...$params) {

        //$this->middle(false);
        if ($condition == false) {
            return Obj::ins();
        }

        //创建中间件对象
        $class = explode('\\', get_class($this));
        $class = $class[count($class)-1];
        $middle = Pool::object("middle\\{$class}",[$this]);

        array_unshift($params,$function);
        array_unshift($params,$this);

        //在真正触发中间件函数前，可以提前设置中间件的回调事件
        method_exists($this,'__middle') and  $this->__middle($middle);

        return call_user_func_array([$middle,'middle'],$params);
    }

    /**
     * 设置当参数验证失败时的回调函数
     * @param $args 验证失败的参数名称
     * @param $msg 失败原因
     */
    public function __error($msg,$args) {
        Pool::object('nb\\event\\Framework')->validate(
            $msg,
            $args
        );
    }

    public function __get($name) {
        switch($name) {
            case 'isPost':
                return Request::driver()->isPost;
            case 'isGet':
                return Request::driver()->isGet;
            case 'isAjax':
                return Request::driver()->isAjax;
            default:
                $method = '_' . $name;
                if (method_exists($this, $method)) {
                    return  $this->$method();
                }
                return null;
        }
    }

}