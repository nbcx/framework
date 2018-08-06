<?php
namespace nb;

use ArrayAccess;

/**
 * 对象帮手,用于处理空对象方法
 *
 * @copyright Copyright (c) 2008 Typecho team (http://nb.cx)
 * @license GNU General Public License 2.0
 */
class Obj implements ArrayAccess {

    /**
     * 获取单例句柄
     *
     * @access public
     * @return Object
     */
    public static function ins() {
        return \nb\Pool::object(get_called_class());
    }

    /**
     * 检查偏移位置是否存在
     * ArrayAccess
     * @param mixed $key
     * @return bool
     */
    public function offsetExists($key) {
        return null;
    }

    /**
     * 获取一个偏移位置的值
     * ArrayAccess
     * @param mixed $key
     * @return mixed|null
     */
    public function offsetGet($key) {
        //return $this->__get($key);

        //此种方式，可以在以数组形式访问时，直接读取public属性
        return null;
    }

    /**
     * 复位一个偏移位置的值
     * ArrayAccess
     * @param mixed $key
     */
    public function offsetUnset($key) {
    }

    /**
     * 设置一个偏移位置的值
     * ArrayAccess
     * @param mixed $key
     * @param mixed $value
     */
    public function offsetSet($key, $value) {

    }

    /**
     * 所有方法请求直接返回
     *
     * @access public
     * @param string $name 方法名
     * @param array $args 参数列表
     * @return void
     */
    public function __call($name, $args) {
        return;
    }

    public function __get($name) {
        // TODO: Implement __get() method.
        return;
    }

    public function __set($name, $value) {
        // TODO: Implement __set() method.
    }
}
