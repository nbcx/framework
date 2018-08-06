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
 * 组件抽象定义类
 *
 * @package nb
 * @link https://nb.cx
 * @since 2.0
 * @author: collin <collin@nb.cx>
 * @date: 2018/7/25
 */
abstract class Component {

    protected $driver;

    public function __construct() {
        $this->driver =  call_user_func_array(
            'static::driver',
            func_get_args()
        );
    }

    /**
     * 获取驱动对象
     * @return mixed|null
     */
    public static function driver(){
        $alias = get_called_class();
        if($driver = Pool::get($alias)) {
            return $driver;
        }
        $args = func_get_args();
        $config = static::config();

        if(func_num_args()>0) {
            $args[] = $config;
        }
        else {
            $args =  $config?[$config]:[];
        }
        $class = self::parse($alias,$config);

        return Pool::object($alias,$class,$args);
        /*
        $reflection = new \ReflectionClass($class);

        return Pool::set($alias,call_user_func_array(
            [&$reflection, 'newInstance'],
            $args
        ));
        */
    }

    /**
     *
     * @param $class 子类全路径
     * @param null $args
     * @throws \ReflectionException
     */
    protected static function parse($class,$config) {
        $class = strtolower($class);
        if(isset($config['driver']) && $config['driver']) {

            if(strpos('/',$config['driver'])) {
                $class = $config['driver'];
            }
            else {
                //如果无'/'，表明驱动为内置类
                $class .= '\\'.ucfirst($config['driver']);
            }
        }
        else {
            switch (Config::$o->sapi) {
                case 'swoole':
                    $class .= class_exists($class . '\\Swoole')?'\\Swoole':'\\Native';
                    break;
                case 'cli':
                    $class .= class_exists($class . '\\Command')?'\\Command':'\\Native';
                    break;
                default :
                    $class .= '\\Native';
                    break;
            }
        }
        return $class;
    }

    public static function config() {
        get_called_class();
        return null;
    }

    /**
     * 绑定类的静态代理
     *
     * @static
     * @access public
     * @param  string|array  $name    类标识
     * @param  string        $class   类名
     * @return object
     */
    public static function bind($name, $class = null) {
        if($class) {
            Pool::object($name,$class);
        }
        else{
            Pool::object(get_called_class(),$name);
        }
    }

    /**
     * 切换驱动对象
     *
     * @param $config
     * @return mixed
     */
    public static function change($config) {
        $class = get_called_class();
        $driver = is_array($config)?$config['driver']:$config;
        if(strpos('/',$driver)) {
            $class = $driver;
        }
        else {
            $class = strtolower($class);
            //如果无'/'，表明驱动为内置类
            $class .= '\\'.ucfirst($driver);
        }
        return Pool::set($class,new $class($config));
    }


    /**
     * 清除内存池里的驱动对象
     */
    public static function remove() {
        Pool::remove(get_called_class());
    }

    /**
     * 对类库里的方法静态调用
     * @param $name
     * @param $arguments
     * @return self
     */
    public static function __callStatic($method, $arguments) {
        // TODO: Implement __callStatic() method.
        $that = static::driver();
        if (method_exists($that, $method)) {
            return call_user_func_array([$that,$method],$arguments);
        }
        return null;
    }

    public function __call($name, $arguments) {
        // TODO: Implement __call() method.
        return call_user_func_array([$this->driver,$name],$arguments);
    }

    public function __set($name, $value) {
        // TODO: Implement __set() method.
        return $this->driver->$name = $value;
    }

    public function __get($name) {
        // TODO: Implement __get() method.
        return $this->driver->$name;
    }

}
