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

/**
 * Native
 *
 * @package nb\response
 * @link https://nb.cx
 * @since 2.0
 * @author: collin <collin@nb.cx>
 * @date: 2017/11/28
 */
class Php extends Driver {

    /**
     * @var string
     */
    public $version = '1.1';

    /**
     * @var int
     */
    protected $statusCode = 200;

    /**
     * @var string
     */
    protected $statusText;

    /**
     * @var string
     */
    protected $body = '';

    /**
     * @var array
     */
    protected $httpHeaders = [];


    /**
     * Converts the response object to string containing all headers and the response content.
     *
     * @return string The response with headers and content
     */
    public function __toString() {
        $headers = [];
        foreach ($this->httpHeaders as $name => $value) {
            $headers[$name] = (array)$value;
        }

        return sprintf('HTTP/%s %s %s', $this->version, $this->statusCode, $this->statusText) . "\r\n" .
            $this->getHttpHeadersAsString($headers) . "\r\n" .
            $this->getResponseBody();
    }

    /**
     * Returns the build header line.
     *
     * @param string $name The header name
     * @param string $value The header value
     *
     * @return string The built header line
     */
    protected function buildHeader($name, $value) {
        return sprintf("%s: %s\n", $name, $value);
    }

    /**
     * @param int $statusCode
     * @param string $text
     * @throws InvalidArgumentException
     */
    public function code($statusCode, $text = null) {
        $this->statusCode = (int)$statusCode;
        if ($this->isInvalid()) {
            throw new \InvalidArgumentException(sprintf('The HTTP status code "%s" is not valid.', $statusCode));
        }

        $this->statusText = false === $text ? '' : (null === $text ? $this->statusTexts[$this->statusCode] : $text);
        return $this;
    }

    /**
     * @return Boolean
     *
     * @api
     *
     * @see http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html
     */
    public function isInvalid() {
        return $this->statusCode < 100 || $this->statusCode >= 600;
    }


    /**
     * @param string $name
     * @param mixed $value
     */
    public function header($name, $value) {
        $this->httpHeaders[$name] = $value;
    }

    /**
     * @param string $format
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function body($content) {
        $this->body = $content;
        return $this;
    }

    /**
     * @param string $format
     */
    public function send($format = 'html') {
        // headers have already been sent by the developer
        if (headers_sent()) {
            return;
        }
        $this->header('Content-Type', $this->mimeType[$format]);
        if(is_array($this->body)) {
            switch ($format) {
                case 'json':
                    $this->body = json_encode($this->body);
                    break;
                case 'xml':
                    $xml = new \SimpleXMLElement('<response/>');
                    foreach ($this->body as $key => $param) {
                        $xml->addChild($key, $param);
                    }
                    $this->body = $xml->asXML();
                    break;
            }
        }
        // status
        header(sprintf('HTTP/%s %s %s', $this->version, $this->statusCode, $this->statusText));

        foreach ($this->httpHeaders as $name => $header) {
            //ed(sprintf('%s: %s', $name, $header),$this->httpHeaders);
            header(sprintf('%s: %s', $name, $header));
        }
        echo $this->body;
    }

    /**
     * @param int $statusCode
     * @param string $error
     * @param string $errorDescription
     * @param string $errorUri
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function error($statusCode, $error, $errorDescription = null, $errorUri = null) {
        $parameters = [
            'error' => $error,
            'error_description' => $errorDescription,
        ];

        if (!is_null($errorUri)) {
            if (strlen($errorUri) > 0 && $errorUri[0] == '#') {
                // we are referencing an oauth bookmark (for brevity)
                $errorUri = 'http://tools.ietf.org/html/rfc6749' . $errorUri;
            }
            $parameters['error_uri'] = $errorUri;
        }

        $this->code($statusCode);
        $this->body($parameters);
        $this->header('Cache-Control', 'no-store');
        return $this;
    }

    /**
     * @param int $statusCode
     * @param string $url
     * @param string $state
     * @param string $error
     * @param string $errorDescription
     * @param string $errorUri
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function redirect($url, $statusCode=302, $state = null, $error = null, $errorDescription = null, $errorUri = null) {
        if (empty($url)) {
            throw new InvalidArgumentException('Cannot redirect to an empty URL.');
        }

        $parameters = [];

        if (!is_null($state)) {
            $parameters['state'] = $state;
        }

        if (!is_null($error)) {
            $this->error(400, $error, $errorDescription, $errorUri);
        }
        $this->code($statusCode);
        $this->addParameters($parameters);

        if (count($this->parameters) > 0) {
            // add parameters to URL redirection
            $parts = parse_url($url);
            $sep = isset($parts['query']) && !empty($parts['query']) ? '&' : '?';
            $url .= $sep . http_build_query($this->parameters);
        }

        $this->addHttpHeaders(['Location' => $url]);

        if (!$this->isRedirection()) {
            throw new InvalidArgumentException(sprintf('The HTTP status code is not a redirect ("%s" given).', $statusCode));
        }
    }


    /**
     * Function from Symfony2 HttpFoundation - output pretty header
     *
     * @param array $headers
     * @return string
     */
    private function getHttpHeadersAsString($headers) {
        if (count($headers) == 0) {
            return '';
        }

        $max = max(array_map('strlen', array_keys($headers))) + 1;
        $content = '';
        ksort($headers);
        foreach ($headers as $name => $values) {
            foreach ($values as $value) {
                $content .= sprintf("%-{$max}s %s\r\n", $this->beautifyHeaderName($name) . ':', $value);
            }
        }

        return $content;
    }

    /**
     * Function from Symfony2 HttpFoundation - output pretty header
     *
     * @param string $name
     * @return mixed
     */
    private function beautifyHeaderName($name) {
        return preg_replace_callback('/\-(.)/', [$this, 'beautifyCallback'], ucfirst($name));
    }

    /**
     * Function from Symfony2 HttpFoundation - output pretty header
     *
     * @param array $match
     * @return string
     */
    private function beautifyCallback($match) {
        return '-' . strtoupper($match[1]);
    }

    /**
     * @return Boolean
     *
     * @api
     */
    public function isSuccessful() {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }

    /**
     * @return Boolean
     *
     * @api
     */
    public function isRedirection() {
        return $this->statusCode >= 300 && $this->statusCode < 400;
    }


    /**
     * 返回来路
     *
     * @access public
     * @param string $suffix 附加地址
     * @param string $default 默认来路
     */
    public function goBack($suffix = NULL, $default = NULL) {
        //获取来源
        $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';

        //判断来源
        if ($referer) {
            // ~ fix Issue 38
            if ($suffix) {
                $parts = parse_url($referer);
                $myParts = parse_url($suffix);

                if (isset($myParts['fragment'])) {
                    $parts['fragment'] = $myParts['fragment'];
                }

                if (isset($myParts['query'])) {
                    $args = [];
                    if (isset($parts['query'])) {
                        parse_str($parts['query'], $args);
                    }

                    parse_str($myParts['query'], $currentArgs);
                    $args = array_merge($args, $currentArgs);
                    $parts['query'] = http_build_query($args);
                }
                $referer = $this->buildUrl($parts);
            }
            redirect($referer, false);
        }

        if ($default) {
            redirect($default);
        }
        exit;
    }
}