<?php
/*
 * This file is part of the NB Framework package.
 *
 * Copyright (c) 2018 https://nb.cx All rights reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace nb\debug;

use nb\Config;

/**
 * Driver
 *
 * @package nb\debug
 * @link https://nb.cx
 * @since 2.0
 * @author: collin <collin@nb.cx>
 * @date: 2017/12/3
 */
abstract class Driver {

	/**
	 * @return Debug
	 */
	abstract public function start($synchronous = true);

	/**
	 *
	 * @param $type
	 * @param $key
	 * @param $val
	 */
    abstract public function record($type,$parama,$paramb=null);

	/**
	 * 统计信息，存入Bug
	 */
    abstract public function end();


    /**
     * 获取已经存在的Debug日志
     * @return object
     */
    protected function get(){
        $bpath = Config::getx('path_temp');
        if(is_file($bpath.'debug.log')){
            return json_decode(file_get_contents($bpath.'debug.log'),true);
        }
        return null;
    }

    /**
     * 记录Debug日志
     * @param $log
     * @throws \Exception
     */
    protected function put($log) {
        $bpath = Config::getx('path_temp');
        if (!is_dir($bpath) && !mkdir($bpath,0777,true)) {
            throw new \Exception('Create bug dir is fail!');
        }
        file_put_contents($bpath.'debug.log', json_encode($log));
    }

    /**
     * 中断程序运行
     * @param $status
     */
    public function die($status) {
        die($status);
    }

}