<?php
/*
 * This file is part of the NB Framework package.
 *
 * Copyright (c) 2018 https://nb.cx All rights reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace nb\event;

use nb\Config;
use nb\Debug;
use nb\Pool;

/**
 * Framework
 *
 * @package nb\event
 * @link https://nb.cx
 * @since 2.0
 * @author: collin <collin@nb.cx>
 * @date: 2017/12/2
 */
class Swoole {

    /*
     * 在task_worker进程内被调用。
     * worker进程可以使用swoole_server_task函数向task_worker进程投递新的任务。
     * 当前的Task进程在调用onTask回调函数时会将进程状态切换为忙碌，
     * 这时将不再接收新的Task，当onTask函数返回时会将进程状态切换为空闲然后继续接收新的Task。
     *
     * onTask函数执行时遇到致命错误退出，或者被外部进程强制kill，
     * 当前的任务会被丢弃，但不会影响其他正在排队的Task
     */
    public function task(\swoole\Server $serv, $task_id, $src_worker_id, $data) {
        $result = null;
        Debug::start(false);
        try{
            $do = $data[0];
            $args = $data[1];
            $class = $do[0];
            $class = new $class($serv,$task_id,$src_worker_id);
            $func = $do[1];
            $result = call_user_func_array([$class,$func],$args);
        }
        catch (\Exception $e) {
            Debug::exception($e);
        }
        Debug::end();
        Pool::destroy();
        return $result;
    }

    /*
     * 当worker进程投递的任务在task_worker中完成时，
     * task进程会通过swoole_server->finish()方法将任务处理的结果发送给worker进程。
     *
     *
     * task进程的onTask事件中没有调用finish方法或者return结果，worker进程不会触发onFinish
     * 执行onFinish逻辑的worker进程与下发task任务的worker进程是同一个进程
     */
    public function finish(\swoole\Server $serv, $task_id, $data) {
        l('finish--$task_id:'.$task_id);
        l('finish--$data:'.$data);
    }

    /**
     * Server启动在主进程的主线程回调此函数
     * 在此事件之前Swoole Server已进行了如下操作
     *   已创建了manager进程
     *   已创建了worker子进程
     *   已监听所有TCP/UDP端口
     *   已监听了定时器
     * onStart回调中，仅允许echo、打印Log、修改进程名称。不得执行其他操作。
     * onWorkerStart和onStart回调是在不同进程中并行执行的，不存在先后顺序。
     * @param swoole_server $server
     */
    public function start(\swoole\Server $server) {
        $pid = posix_getpid();
        $path = Config::getx('swoole.enable_pid');
        if ($path) {
            file_put_contents($path, $pid);
        }
        else {
            file_put_contents('/tmp/swoole.pid', $pid);
        }
    }

    /**
     * 当服务关闭时触发
     * 在此之前Swoole Server已进行了如下操作
     *   已关闭所有线程
     *   已关闭所有worker进程
     *   已close所有TCP/UDP监听端口
     *   已关闭主Rector
     * @param swoole_server $server
     */
    public function shutdown(\swoole\Server $server) {
        $pid_file = '/tmp/swoole.pid';
        $path = Config::getx('swoole.enable_pid');
        if ($path) {
            $pid_file = $path;
        }
        if (file_exists($pid_file)) {
            unlink($pid_file);
        }
    }

    /**
     * 此事件在Worker进程/Task进程启动时发生。这里创建的对象可以在进程生命周期内使用
     *
     * 发生致命错误或者代码中主动调用exit时，Worker/Task进程会退出，管理进程会重新创建新的进程
     * onWorkerStart/onStart是并发执行的，没有先后顺序
     * 可以通过$server->taskworker属性来判断当前是Worker进程还是Task进程
     *
     * @param \swoole\Server $server
     * @param $worker_id
     */
    /*
    public function workerStar(\swoole\Server $server,$worker_id) {
        $conf = Config::$o->swoole;
        if($worker_id >= $conf['worker_num']) {
            swoole_set_process_name("php-{$conf['part']}-task-worker");
        }
        else {
            swoole_set_process_name("php-{$conf['part']}-event-worker");
        }
    }
    */

}