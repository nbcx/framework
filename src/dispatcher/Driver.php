<?php
/*
 * This file is part of the NB Framework package.
 *
 * Copyright (c) 2018 https://nb.cx All rights reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace nb\dispatcher;

use nb\Config;

/**
 * Driver
 *
 * @package nb\dispatcher
 * @link https://nb.cx
 * @since 2.0
 * @author: collin <collin@nb.cx>
 * @date: 2017/12/3
 */
abstract class Driver {

    abstract public function run();

    abstract public function go($class,$function);

    protected function module($module) {
        //return true;

        $conf = Config::$o;
        $conf_file = __APP__.$conf->folder_module.DS.$module.DS.'config.inc.php';

        if(is_file($conf_file)) {
            $config = include $conf_file;
            if(isset($config['path_autoinclude'])) {
                $path_autoext = isset($config['path_autoext'])?$config['path_autoext']:$conf->path_autoext;
                $conf->import(
                    $config['path_autoinclude'],
                    $path_autoext
                );
                unset($config['path_autoinclude']);
            }
            if(isset($config['view'])) {
                Config::setx_merge('view',$config['view']);
                unset($config['view']);
            }

            foreach ($config as $k=>$v) {
                $conf->$k = $v;
                //Config::setx($k,$v);
            }
        }
        else {
            //$conf->view['view_path'] = __APP__.$conf->module.DS.$module.DS.'view'.DS;
            $conf->import(
                [__APP__.$conf->folder_module.DS.$module.DS.'include'.DS],
                $conf->path_autoext
            );
        }
        return true;
    }

}