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
use nb\Debug;
use nb\Router;
use nb\Pool;

/**
 * Command
 *
 * @package nb\debug
 * @link https://nb.cx
 * @since 2.0
 * @author: collin <collin@nb.cx>
 * @date: 2017/12/3
 */
class Command extends Driver {

	private $show = [];
	private $key;
	private $ip;
	private $n = 10;
	private $page = false;

	private $record;

	private $startd = false;

    public function __construct() {
        $config = Config::getx('debug');
        if(!$config || $config === true) {
            return;
        }
        if(is_string($config)) {
            $config = explode(",",$config);
        }

        foreach($config as $v){
            $ex = explode(':',$v);
            switch ($ex[0]){
                case 'show':
                    $this->show = explode('-',$ex[1]);
                    break;
                case 'key':
                    $this->key = $ex[1];
                    break;
                case 'ip':
                    $this->ip = explode('-',$ex[1]);
                    break;
                case 'n':
                    $this->n = $ex[1];
                    break;
				case 'page':
					$this->page = $ex[1];
					break;
                default:
                    break;
            }
        }
    }


	/**
	 * @return Debug
	 */
	public function start($synchronous = true){
        $this->record['mem'] =  array_sum(explode(' ',memory_get_usage()));
        $this->record['start'] = time();
        $this->record['spend'] = microtime(true);
        $this->record['sql'] = [];
        $this->record['exception'] = [];
        $this->record['log'] = [];
        $this->record['method'] = 'cli';
        $this->record['ip'] = '0.0.0.0';
        $this->startd = true;
	}


	/**
	 *
	 * @param $type
	 * @param $key
	 * @param $val
	 */
	public function record($type,$parama,$paramb=null){
        switch($type) {
            case 1:
                $this->record['log'][] = ['k'=>$parama,'v'=>$paramb];
                break;
            case 2:
                $parama = Debug::e2Array($parama);
                $this->record['e'][] = $parama;
                break;
            case 3:
                $this->record['sql'][] = ['sql'=>$parama,'param'=>$paramb];
                break;
        }
	}

	/**
	 * 统计信息，存入Bug
	 */
	public function end(){
        //如果请求的控制器是debug就算了！
        if (Router::ins()->controller == 'debug') {
            return false;
        }

        if($this->startd === false) {
            return false;
        }

        if(!Pool::object('nb\\event\\Framework')->debug()) {
            return false;
        }

		$record = $this->record;
		$log = $this->get();

        $record['url'] = 'cli:'.\nb\Router::ins()->controller.'/'.\nb\Router::ins()->function;


        $record['spend'] = round(microtime(true)-$record['spend'],3);
        $record['mem'] = number_format((array_sum(explode(' ',memory_get_usage())) - $record['mem'])/1024).'kb';

		if(in_array('all',$this->show)||in_array('trace',$this->show)){
            $record['runfile'] = get_included_files();
		}
		if(empty($log)){
			$log[] = $record;
		}
		else{
			$n = array_unshift($log, $record);//向数组插入元素
			if($n >= $this->n) unset($log[$this->n]);
		}
		$this->put($log);
	}




}
