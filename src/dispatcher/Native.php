<?php
/*
 * This file is part of the NB Framework package.
 *
 * Copyright (c) 2018 https://nb.cx All rights reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace nb\dispatcher;

use nb\Config;
use nb\Request;
use nb\Router;
use nb\Validate;
use nb\Pool;

/**
 * Driver
 *
 * @package nb\dispatcher
 * @link https://nb.cx
 * @since 2.0
 * @author: collin <collin@nb.cx>
 * @date: 2017/12/3
 */
class Native extends Driver {

    /**
     * 当前请求的参数(控制器指定了method或自动获取的)
     * @var array
     */
    public $input = [];
    /**
     * 当前控制器需要的参数
     * @var array
     */
    public $params = [];

    /**
     * 处理网络请求
     */
    public function run() {
        //路由解析前的回调函数
        //可以重定路由，可以修改路由配置等
        Pool::object('nb\\event\\Framework')->redirect();

        //判断是否为模块绑定
        $module = Config::$o->module_bind;
        if($module && isset($module[$host = Request::ins()->host])) {
            $this->module($module[$host]);
            $router = Router::ins();
            $router->module = $module[$host];
            $router->mustAnalyse();
        }
        else {
            $router = Router::ins()->mustAnalyse();
            //如果访问的模块，加载模块配置
            if($router->module) {
                $this->module($router->module);
            }
        }

        //如果请求的Action为Debug，则打开debug页面
        switch ($router->controller) {
            case 'debug':
                $this->debug($router);
                break;
            default :
                //如果加载不成功，作为404处理
                //过滤掉禁止访问的方法
                $class = $router->class;//$this->load($router);
                if(!$class || in_array($router->function,Config::$o->notFunc)) {
                    return Pool::object('nb\\event\\Framework')->notfound();
                }
                //过滤掉禁止访问的方法
                //if (in_array($router->function,Config::$o->notFunc)) {
                //    return Pool::object('nb\\event\\Framework')->notfound();
                //}
                $this->go($class,$router->function);
                break;
        }
    }

    public function go($class, $function) {
        // TODO: Implement dowith() method.
        $class = new \ReflectionClass($class);

        //创建当前控制器对象，并放入池子
        $app = Pool::value('controller',$class->newInstance());

        //获取此次请求的参数
        $method = 'request';
        if ($class->hasProperty('_method')) {
            $method = $app->_method;
        }
        $this->input = Request::driver()->form($method);
        $pubparams = [];
        $scene = [];
        $_before_argsn = [];
        $_function_argsn = [];

        if ($_hasbefore = $class->hasMethod('__before')) {
            $_before = new \ReflectionMethod($app, '__before');
            $_before_argsn = $_before->getNumberOfParameters();
            if($_before_argsn>0) {
                $args =  $_before->getParameters();
                $this->verification($args,$pubparams,$scene, $class, $app);
            }
        }

        if ($_hasfunction = $class->hasMethod($function)) {
            $_function = new \ReflectionMethod($app, $function);
            $_function_argsn = $_function->getNumberOfParameters();
            if($_function_argsn>0) {
                $args =  $_function->getParameters();
                $this->verification($args,$this->params,$scene, $class, $app);
            }
        }

        $scene = array_unique($scene);
        $param = array_unique(array_merge($pubparams,$this->params));

        $validate = null;
        $rule = $class->hasProperty('_rule');
        if($rule && $app->_rule ) {
            $validate = $class->hasProperty('_message')?Validate::make($app->_rule,$app->_message):Validate::make($app->_rule);
        }

        if($validate && ($_before_argsn || $_function_argsn) ) {
            $validate->scene($function, $scene);
            $result = $validate->scene($function)->check($param);
            if(!$result) {
                if ($class->hasMethod('__error')) {
                    return $app->__error($validate->error,$validate->field);
                }
                return Pool::object('nb\\event\\Framework')->validate(
                    $validate->error,
                    $validate->field
                );
            }
        }

        //判断用户是否构建了__before方法,如果构建，则只有__before为true，才进行处理
        $_hasbefore = $_hasbefore?call_user_func_array([$app,'__before'], $pubparams):true;
        $return = null;
        if ($_hasbefore) {
            if($_hasfunction) {
                if (!$_function->isPublic() || $_function->isStatic()) {
                    Pool::object('nb\\event\\Framework')->notfound();
                    return;
                }
                $params = $this->params?:[];
                $return = call_user_func_array([$app,$function],$params);

            }
            else {
                return Pool::object('nb\\event\\Framework')->notfound();
            }
        }

        //判断用户是否构建了__after方法,如果构建，则执行
        if ($class->hasMethod('__after')) {
            $app->__after($return);
        }
    }

    /**
     * 处理debug
     * @param NRouter $url
     */
    protected function debug(\nb\router\Driver &$url) {
        if (Config::$o->debug) {
            \nb\Debug::driver()->index();
            //$bug = new Debug();
            return;// $bug->index();
        }
        Pool::object('nb\\event\\Framework')->notfound($url);
    }

    /**
     * 包装需要验证的参数
     * @param $args
     * @param $param
     * @param $scene
     * @throws Exception
     */
    private function verification($args,&$param,&$scene, $r, $app){
        foreach ($args as $v) {
            $scene[] = $v->name;

            if(isset($this->input[$v->name])){
                if(is_array($this->input[$v->name])) {
                    $param[$v->name] = $this->input[$v->name];
                }
                else if(strlen($this->input[$v->name])>0) {
                    $param[$v->name] = $this->input[$v->name];
                }
                else if($v->isDefaultValueAvailable()) {
                    $param[$v->name]=$v->getDefaultValue();
                }
                else {
                    if ($r->hasMethod('__error')) {
                        $app->__error("{$v->name}参数为必须参数!",$v->name);
                    }
                    else {
                        Pool::object('nb\\event\\Framework')->validate(
                            "{$v->name}参数为必须参数!",
                            $v->name
                        );
                    }
                    quit();
                }
            }
            else if($v->isDefaultValueAvailable()) {
                $param[$v->name]=$v->getDefaultValue();
            }
            else {
                if ($r->hasMethod('__error')) {
                    $app->__error("{$v->name}参数为必须参数!",$v->name);
                }
                else {
                    Pool::object('nb\\event\\Framework')->validate(
                        "{$v->name}参数为必须参数!",
                        $v->name
                    );
                }
                quit();
            }
        }
    }

}