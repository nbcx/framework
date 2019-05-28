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
use nb\Access;

/**
 * Driver
 *
 * @package nb\response
 * @link https://nb.cx
 * @since 2.0
 * @author: collin <collin@nb.cx>
 * @date: 2017/11/28
 */
abstract class Driver extends Access {

    /**
     * @var array 资源类型
     */
    protected  $mimeType = [
        'bmp'  => 'image/bmp',
        'ico'  => 'image/x-icon',
        'gif'  => 'image/gif',
        'png'  => 'image/png',
        'bin'  => 'application/octet-stream',
        'css'  => 'text/css',
        'tar'  => 'application/x-tar',
        'ppt'  => 'application/vnd.ms-powerpoint',
        'pdf'  => 'application/pdf',
        'swf'  => 'application/x-shockwave-flash',
        'zip'  => 'application/x-zip-compressed',
        'gzip' => 'application/gzip',
        'woff' => 'application/x-woff',
        'svg'  => 'image/svg+xml',
        'xml'  => 'application/xml,text/xml',
        'json' => 'application/json,text/x-json,application/jsonrequest,text/json',
        'js'   => 'text/javascript,application/javascript,application/x-javascript',
        'rss'  => 'application/rss+xml',
        'yaml' => 'application/x-yaml,text/yaml',
        'atom' => 'application/atom+xml',
        'text' => 'text/plain',
        'jpg'  => 'image/jpg,image/jpeg,image/pjpeg',
        'csv'  => 'text/csv',
        'html' => 'text/html,application/xhtml+xml,*/*',
    ];

    /**
     * http code
     *
     * @access private
     * @var array
     */
    protected $statusTexts = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        307 => 'Temporary Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported'
    ];

    //abstract public function header($key, $value=null,$http_response_code=null);

    /**
     * @param int $statusCode
     */
    abstract public function code($statusCode, $text = null);

    /**
     * @param int $statusCode
     * @param string $name
     * @param string $description
     * @param string $uri
     * @return mixed
     */
    abstract public function error($statusCode, $name, $description = null, $uri = null);

    /**
     * @param int $statusCode
     * @param string $url
     * @param string $state
     * @param string $error
     * @param string $errorDescription
     * @param string $errorUri
     * @return mixed
     */
    abstract public function redirect($url, $statusCode=302, $state = null, $error = null, $errorDescription = null, $errorUri = null);


    abstract public function send($format = 'json');
}