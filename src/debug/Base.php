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

/**
 * Base
 *
 * @package nb\src\debug
 * @link https://nb.cx
 * @author: collin <collin@nb.cx>
 * @date: 2018/8/7
 */
class Base extends Command {

    //是否已经中断程序运行了
    private $died = false;

    /**
     * 中断程序运行
     * @param $status
     */
    public function quit($status) {
        if($this->died) {
            return;
        }
        $this->died = true;
        if($status) echo $status;
        throw new \Exception('die');
    }
}