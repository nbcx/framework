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

use nb\server\assist\Swoole;

/**
 * Websocket
 *
 * @package nb\server
 * @link https://nb.cx
 * @since 2.0
 * @author: collin <collin@nb.cx>
 * @date: 2017/12/1
 */
class Websocket extends Swoole {

    protected $options = [
        'driver'=>'websocket',
        'register'=>'',//注册一个类，来实现swoole自定义事件
        'host'=>'0.0.0.0',
        'port'=>9502,
        'max_request'=>'',//worker进程的最大任务数
        'worker_num'=>'',//设置启动的worker进程数。
        'dispatch_mode'=>2,//据包分发策略,默认为2
        'debug_mode'=>3,
        'enable_gzip'=>0,//是否启用压缩，0为不启用，1-9为压缩等级
        'enable_log'=>'tmp'.DS.'swoole-http.log',
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
        'managerStop',
        'message',
        'open',
        'handShake'
    ];

    public function run() {
        //设置server参数
        $ser = $this->server = new \swoole\websocket\Server($this->options['host'], $this->options['port']);
        $ser->set($this->options);

        //设置server回调事件
        $ser->on('open',[$this,'open']);
        $ser->on('message',[$this,'message']);
        $ser->on('close',[$this,'close']);
        $callback = new $this->options['register']();
        foreach ($this->call as  $v) {
            $ser->on($v,[$callback,$v]);
        }

        //启动server
        $ser->start();
    }

    public function open(\swoole\Server $server, $req) {
        $server->on('open', function($server, $req) {
            echo "connection open: {$req->fd}\n";
        });
    }

    public function message(\swoole\Server $server, $frame) {
        echo "received message: {$frame->data}\n";
        $server->push($frame->fd, json_encode(["hello", "world"]));
    }

    public function close(\swoole\Server $server, $fd) {
        echo "connection close: {$fd}\n";
    }

}