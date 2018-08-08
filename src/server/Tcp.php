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
        'port'=>9502,
        'max_request'=>'',//worker进程的最大任务数
        'worker_num'=>'',//设置启动的worker进程数。
        'dispatch_mode'=>2,//据包分发策略,默认为2
        'debug_mode'=>3,
        'enable_gzip'=>0,//是否启用压缩，0为不启用，1-9为压缩等级
        'enable_log'=>__APP__.'tmp'.DS.'swoole-tcp.log',
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

    public function run() {
        //设置server参数
        $server = new \swoole\Server($this->options['host'], $this->options['port']);
        $server->set($this->options);

        //设置server回调事件
        $server->on('receive',[$this,'receive']);
        $callback = new $this->options['register']();
        foreach ($this->call as  $v) {
            $server->on($v,[$callback,$v]);
        }
        $this->swoole = $server;
        //启动server
        $server->start();
    }


    public function receive($server, $fd, $reactor_id, $data) {
        try {
            Config::$o->sapi='tcp';
            ob_start();
            Pool::destroy();
            Dispatcher::run($data);
        }
        catch (\Throwable $e) {
            $this->error($e);
        }
        Debug::end();
        $server->push($fd,ob_get_contents());
        ob_end_clean();
    }


}