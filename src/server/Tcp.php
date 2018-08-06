<?php
/*
 * This file is part of the NB Framework package.
 *
 * Copyright (c) 2018 https://nb.cx All rights reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace nb\server;

use nb\Config;
use nb\server\assist\Swoole;

/**
 * Tcp
 *
 * @package nb\server
 * @link https://nb.cx
 * @since 2.0
 * @author: collin <collin@nb.cx>
 * @date: 2017/12/1
 */
class Tcp extends Swoole {

    protected $options = [
        'driver'=>'tcp',
        'register'=>'\\nb\\event\\Swoole',//注册一个类，来实现swoole自定义事件
        'host'=>'0.0.0.0',
        'port'=>9503,
        'max_request'=>'',//worker进程的最大任务数
        'worker_num'=>'',//设置启动的worker进程数。
        'dispatch_mode'=>2,//据包分发策略,默认为2
        'debug_mode'=>3,
        'enable_gzip'=>0,//是否启用压缩，0为不启用，1-9为压缩等级
        'enable_log'=>'tmp'.DS.'swoole-tcp.log',
        'enable_pid'=>'/tmp/swoole.pid',
        'daemonize'=>true
    ];

    protected $call = [
        'start',
        'shutdown',
        'workerStart',
        'workerStop',
        'workerExit',
        'timer',
        'connect',
        'receive',
        'packet',
        'close',
        'bufferFull',
        'bufferEmpty',
        'task',
        'finish',
        'pipeMessage',
        'workerError',
        'managerStart',
        'managerStop'
    ];

    public function __construct($options=[]) {
        $this->options = array_merge($this->options,$options);
        $register = get_class_methods($this->options['register']);
        $register and $this->call = array_intersect($this->call,$register);
    }


    public function run() {
        //设置server参数
        $ser = $this->server = new \swoole\Server($this->options['host'], $this->options['port']);
        $ser->set($this->options);

        //设置server回调事件
        $ser->on('connect',[$this,'connect']);
        $ser->on('receive',[$this,'receive']);
        $ser->on('close',  [$this,'close']);
        $callback = new $this->options['register']();
        foreach ($this->call as  $v) {
            $ser->on($v,[$callback,$v]);
        }
        //启动server
        $ser->start();
    }

    public function connect($server, $fd) {
        echo "connection open: {$fd}\n";
    }

    public function receive($server, $fd, $reactor_id, $data) {
        $server->send($fd, "service: {$data}");
        //$server->close($fd);
    }

    public function close($server, $fd) {
        echo "connection close: {$fd}\n";
    }

}