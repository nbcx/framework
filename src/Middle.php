<?php
/**
 *
 * User: Collin
 * QQ: 1169986
 * Date: 2018/5/26 上午8:28
 */
namespace nb;

class Middle {

    public $status = null;

    public $success;
    public $fail;

    protected $on = [];

    protected $controller;

    public function __construct(Controller $controller) {
        $this->controller = $controller;
    }

    protected function form(...$params){
        return call_user_func_array(
            [$this->controller,'form'],
            $params
        );
    }

    protected function input(...$params){
        return call_user_func_array(
            [$this->controller,'input'],
            $params
        );
    }

    /**
     * 获取对象插件句柄
     *
     * @access public
     * @param string $handle 句柄
     * @return \nb\Hook
     */
    protected function hook($handle = NULL) {
        return \nb\Hook::pos(empty($handle) ? get_class($this) : $handle);
    }

    /**
     * 设置成功或失败后触发的事件
     *
     * @param $type success|fail
     * @param $callback
     */
    public function on($type,$callback) {
        $this->on[$type] = $callback;
    }

    /**
     * 中间件条件触发
     *
     * @param $that
     * @param null $function
     * @param mixed ...$params
     * @return $this|Collection
     */
    public function middle($that,$function=null,...$params) {

        //$this->middle(false);
        //if ($condition == false) {
        //    return new Collection();
        //}

        //创建中间件对象
        //$class = explode('\\',get_class($that));
        //$class = $class[count($class)-1];
        //$middle = Pool::object("middle\\{$class}",[$that]);

        //$this->middle(true);
        if($function === null) {
            return $this;
        }

        //$this->middle(true,'login');
        if(!$params) {
            $this->status = $this->$function();
            return $this;
        }

        //$this->middle(true,'login',function{});
        //$this->middle(true,'login','args1',function{});
        //$this->middle(true,'login','args1','args2',function{});
        $count = count($params)-1;
        if($params[$count] instanceof \Closure) {
            $call = $params[$count];
            unset($params[$count]);
            $this->status = call_user_func_array([$this,$function],$params);
            if($this->status) {
                return call_user_func($call,$this->success);
            }
            else if(isset($this->on['fail'])) {
                 call_user_func($this->on['fail'],$this->fail);
            }
            return $this;
        }

        //$this->middle(true,'login','args1','args2');
        $this->status = call_user_func_array([$this,$function],$params);
        if($this->status && isset($this->on['success'])) {
            call_user_func($this->on['success'],$this->success);
        }
        else if(isset($this->on['fail'])) {
            call_user_func($this->on['fail'],$this->fail);
        }
        return $this;
    }

    /**
     * 成功后触发回调
     * @param $callback
     */
    public function success($callback) {
        if($this->status) {
            return $callback($this->success);
        }
        return $this;
    }

    /**
     * 失败后触发回调
     * @param $callback
     */
    public function fail($callback) {
        if($this->status == false) {
            return $callback($this->fail);//call_user_func_array($callback,);//
        }
        return $this;
    }



}