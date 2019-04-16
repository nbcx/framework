<?php
/*
 * This file is part of the NB Framework package.
 *
 * Copyright (c) 2018 https://nb.cx All rights reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace nb\response;


use nb\Pool;
use nb\Server;

/**
 * Swoole
 *
 * @package nb\response
 * @link https://nb.cx
 * @since 2.0
 * @author: collin <collin@nb.cx>
 * @date: 2017/11/28
 *
 * @method  \swoole\http\Response end()
 */
class Http extends Php {

    /**
     * @var \swoole\http\Response
     */
    protected $response;


    /**
     * @param array $parameters
     * @param int $statusCode
     * @param array $headers
     */
    public function __construct() {
        $this->response  = Pool::value('\swoole\http\Response');//\nb\driver\Swoole::$o->response;
    }

    public function header($key, $value=null,$http_response_code=null) {
        ob_clean();
        if($value === null) {
            list($key,$value) = explode(':',$key);
        }
        $this->response->header($key,$value);
        if($http_response_code) {
            $this->response->status($http_response_code);
        }
    }

    public function __call($name, $arguments) {
        if(method_exists($this->res,$name)) {
            return call_user_func_array([$this->res,$name],$arguments);
        }
        // TODO: Implement __call() method.
        return null;
    }

    /**
     * @param string $format
     */
    public function send($format = 'json') {
        // headers have already been sent by the developer
        if (headers_sent()) {
            return;
        }

        switch ($format) {
            case 'json':
                $this->setHttpHeader('Content-Type', 'application/json');
                break;
            case 'xml':
                $this->setHttpHeader('Content-Type', 'text/xml');
                break;
        }
        // status
        //header(sprintf('HTTP/%s %s %s', $this->version, $this->statusCode, $this->statusText));
        $this->response->status($this->statusCode);
        foreach ($this->getHttpHeaders() as $name => $header) {
            $this->response->header($name, $header);
        }
        echo $this->getResponseBody($format);
    }

}