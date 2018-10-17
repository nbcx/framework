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
use nb\Debug;
use nb\Dispatcher;
use nb\Pool;

/**
 * Websocket
 *
 * @package nb\server
 * @link https://nb.cx
 * @since 2.0
 * @author: collin <collin@nb.cx>
 * @date: 2017/12/1
 *
 * @property  \swoole\websocket\Server swoole
 */
class Websocket extends Http {

    public $fd;

    protected $options = [
        'driver'=>'websocket',
        'register'=>'',//注册一个类，来实现swoole自定义事件
        'host'=>'0.0.0.0',
        'port'=>9503,
        'max_request'=>'',//worker进程的最大任务数
        'worker_num'=>'',//设置启动的worker进程数。
        'dispatch_mode'=>2,//据包分发策略,默认为2
        'debug_mode'=>3,
        'enable_gzip'=>0,//是否启用压缩，0为不启用，1-9为压缩等级
        'enable_log'=>__APP__.'tmp'.DS.'swoole-socket.log',
        'enable_pid'=>'/tmp/swoole.pid',
        'daemonize'=>true,
        'request'=>false,//启用内置的onRequest回调
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
        'handShake',
        'request'
    ];

    public function run() {
        //设置server参数
        $server = new \swoole\websocket\Server($this->options['host'], $this->options['port']);
        $server->set($this->options);

        //设置server回调事件
        $server->on('message',[$this,'message']);
        $this->options['request'] and $server->on('request',[$this,'request']);
        $callback = new $this->options['register']();
        foreach ($this->call as  $v) {
            $server->on($v,[$callback,$v]);
        }
        $this->swoole = $server;
        //启动server
        $this->swoole->start();
    }

    public function message(\swoole\websocket\Server $server, \swoole\websocket\Frame $frame) {
        try {
            Config::$o->sapi='websocket';
            ob_start();
            Pool::destroy();
            $this->fd = $frame->fd;
            Pool::set('\swoole\websocket\Frame', $frame);
            Dispatcher::run();//$frame->data
        }
        catch (\Throwable $e) {
            $this->error($e);
        }
        Debug::end();
        $data = ob_get_contents() and $this->reply($data);
        ob_end_clean();
    }

    /**
     * @return \swoole\websocket\Frame
     */
    public function frame()     {
        return Pool::get('\swoole\websocket\Frame');
    }

    /**
     * 向客户端发送数据，和Tcp保持一致的API
     * @param int $fd
     * @param string $data
     * @return bool
     */
    public function send($fd, $data){
        if($this->swoole->exist($fd)) {
            return $this->swoole->push($fd,$data);
        }
        return false;
    }

    /**
     * 向当前连接客户端发送数据，和Tcp保持一致的API
     * @param int $fd
     * @param string $data
     * @return bool
     */
    public function reply($data){
        return $this->send($this->fd,$data);
    }

    /**
     * 主动向websocket客户端发送关闭帧并关闭该连接
     *
     * @param int $fd 客户端连接的ID，如果指定的$fd对应的TCP连接并非websocket客户端，将会发送失败
     * @param int $code 关闭连接的状态码，根据RFC6455，对于应用程序关闭连接状态码，取值范围为1000或4000-4999之间
     * @param string $reason 关闭连接的原因，utf-8格式字符串，字节长度不超过125
     * @return mixed 发送成功返回true，发送失败或状态码非法时返回false
     */
    public function close($fd=0, $code = 1000, $reason = "") {
        $fd or $fd = $this->fd;
        return $this->swoole->disconnect($fd,$code,$reason);
    }

}