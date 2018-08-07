<?php
/*
 * This file is part of the NB Framework package.
 *
 * Copyright (c) 2018 https://nb.cx All rights reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace  nb\server\assist;

use nb\Config;
use nb\Console;
use nb\server\Driver;

/**
 * Swoole
 *
 * @package nb\server
 * @link https://nb.cx
 * @since 2.0
 * @author: collin <collin@nb.cx>
 * @date: 2017/11/28
 */
abstract class Swoole extends Driver {

    abstract function run();

    public function start($daemonize=true) {
        echo Console::driver()->logo();
        $this->evenCheck();
        $conf = Config::$o->server;
        if ($pid = $this->getpid()) {
            echo sprintf("other swoole {$conf['driver']} server run at pid %d\n", $pid);
            exit(1);
        }
        echo 'server pattern       '.$conf['driver']."\n";
        echo 'listen address       '.$conf['host']."\n";
        echo 'listen port          '.$conf['port']."\n";
        echo 'worker num           '.$conf['worker_num']."\n";
        echo 'task worker num      '.$conf['task_worker_num']."\n";
        echo 'swoole version       '.phpversion('swoole')."\n\n";
        $this->run($daemonize);
    }

    public function restart() {
        if (!$pid = $this->getpid()) {
            echo "Swoole HttpServer not run\n";
            exit(1);
        }
        posix_kill($pid, SIGTERM);
        echo "swoole httpserver stop\n";
        echo "swoole httpserver start.";
        for($i=1;$i<3;$i++){
            sleep($i);
            echo('.');
        }
        echo "\nrestart +ok\n";
        $this->run();
    }

    public function stop() {
        $conf = Config::$o->server;
        if (!$pid = $this->getpid()) {
            echo "Swoole {$conf['driver']} server not run\n";
            exit(1);
        }
        posix_kill($pid, SIGTERM);
        echo "swoole {$conf['driver']} server stoped\n";
    }

    public function status() {
        $conf = Config::$o->server;
        if ($pid = $this->getpid()) {
            echo sprintf("swoole {$conf['driver']} server run at pid %d \n", $pid);
        }
        else {
            echo "swoole {$conf['driver']} server not run\n";
        }
    }

    public function reload() {
        $conf = Config::$o->server;
        if (!$pid = $this->getpid()) {
            echo "swoole {$conf['driver']} server not run\n";
            exit(1);
        }
        posix_kill($pid, SIGUSR1);
        echo "swoole {$conf['driver']} server reloaded\n";
    }

    public function getpid() {
        $pid_file = '/tmp/swoole.pid';
        $enable_pid = Config::getx('swoole.enable_pid');
        if ($enable_pid) {
            $pid_file = $enable_pid;
        }
        $pid = file_exists($pid_file) ? file_get_contents($pid_file) : 0;
        // 检查进程是否真正存在
        if ($pid && !posix_kill($pid, 0)) {
            $errno = posix_get_last_error();
            if ($errno === 3) {
                $pid = 0;
            }
        }
        return $pid;
    }

    public function evenCheck(){
        if(phpversion() < 5.6){
            $version =  phpversion();
            die("php version must >= 5.6,the current version is {$version}\n");
        }
        if(phpversion('swoole') < 1.8){
            $version =  phpversion('swoole');
            die("swoole version must >= 1.9.5,the current version is {$version}\n");
        }

    }

    public function opCacheClear(){
        if(function_exists('apc_clear_cache')){
            apc_clear_cache();
        }
        if(function_exists('opcache_reset')){
            opcache_reset();
        }
    }

    protected function error($e) {
        //因为需要模拟die函数,所以此处需要catch处理
        if($e->getMessage() === 'die') {
            return;
        }
        throw new \ErrorException(
            $e->getMessage(),
            $e->getCode(),
            1,
            $e->getFile(),
            $e->getLine(),
            $e->getPrevious()
        );
    }

}