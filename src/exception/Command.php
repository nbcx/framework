<?php
/*
 * This file is part of the NB Framework package.
 *
 * Copyright (c) 2018 https://nb.cx All rights reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace nb\exception;

use nb\Config;

/**
 * Command
 *
 * @package nb\exception
 * @link https://nb.cx
 * @since 2.0
 * @author: collin <collin@nb.cx>
 * @date: 2017/12/7
 */
class Command extends Driver {

    protected function show($e,$deadly = false) {
        if (Config::$o->debug) {
            if ($deadly) {
                echo "\n:( Have Error\n";
                echo "CODE: {$e->getCode()} \n";
                echo "FILE: {$e->getFile()} \n";
                echo "LINE: {$e->getLine()}\n";
                echo "DESC: {$e->getMessage()}\n\n";
            }
        }
    }

}