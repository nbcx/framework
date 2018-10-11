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
use nb\console\input\Argument;
use nb\console\input\Input;
use nb\console\input\Option;
use nb\console\output\Output;
use nb\console\Pack;
use nb\console\Command as ICommand;
use nb\Debug;
use nb\Router;
use nb\Pool;


/**
 * Command
 *
 * @package nb\dispatcher
 * @link https://nb.cx
 * @since 2.0
 * @author: collin <collin@nb.cx>
 * @date: 2017/12/3
 */
class Command extends Driver  implements ICommand {

    //请求控制器方法的参数
    protected $params = [];

    public function configure(Pack $cmd) {
        $cmd->setName('action')
            ->addArgument('router', Argument::OPTIONAL, "controller")
            ->addArgument('args', Argument::IS_ARRAY, "parameter list of controller method")
            ->addOption('city', null, Option::VALUE_REQUIRED, 'city name')
            ->setDescription('execute controller in cli');
    }

    public function execute(Input $input, Output $output) {
        Debug::start();
        $this->params = $input->getArgument('args');
        $this->with(trim($input->getArgument('router')));
    }

    function interact(Input $input, Output $output){}

    /**
     * 初始化
     * @param Input $input An InputInterface instance
     * @param Output $output An OutputInterface instance
     */
    function initialize(Input $input, Output $output){}

    /**
     * 处理控制台指令
     */
    public function run() {
        $this->params = $_SERVER['argv'];
        array_splice($this->params,0,2);
        $this->with();
    }

    protected function with($pathinfo=null) {
        //路由解析前的回调函数
        //可以重定路由，可以修改路由配置等
        Pool::object('nb\\event\\Framework')->redirect();

        $router = Router::ins();
        $pathinfo and $router->pathinfo = $pathinfo;
        $router = $router->mustAnalyse();

        //如果访问的模块，加载模块配置
        if($router->module) {
            $this->module($router->module);
        }

        ///如果加载不成功，作为404处理
        $class = $router->class;
        if(!$class) {
            return Pool::object('nb\\event\\Framework')->notfound();
        }
        //过滤掉禁止访问的方法
        if (in_array($router->function,Config::$o->notFunc)) {
            return Pool::object('nb\\event\\Framework')->notfound();
        }
        $this->go($class,$router->function);

    }

    public function go($class, $function) {
        // TODO: Implement go() method.
        $class = new \ReflectionClass($class);

        //创建当前控制器对象，并放入池子
        $app = Pool::value('controller',$class->newInstance());

        $return = null;
        //判断用户是否构建了__before方法,如果构建，则只有__before为true，才进行处理
        if (!$class->hasMethod('__before') || $app->__before()) {
            if ($class->hasMethod($function)) {
                $method = new \ReflectionMethod($app, $function);
                if (!$method->isPublic() || $method->isStatic()) {
                    Pool::object('nb\\event\\Framework')->notfound();
                    return;
                }

                $return = call_user_func_array([$app,$function],$this->params);
            }
            else {
                return Pool::object('nb\\event\\Framework')->notfound();;
            }
        }

        //判断用户是否构建了__after方法,如果构建，则执行
        if ($class->hasMethod('__after')) {
            $app->__after($return);
        }
    }

}