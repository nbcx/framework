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
     * @var int
     */
    protected $statusCode = 200;

    /**
     * @var string
     */
    protected $statusText;

    /**
     * @var array
     */
    protected $parameters = [];

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
            throw new InvalidArgumentException(sprintf('The HTTP status code "%s" is not valid.', $statusCode));
        }

        $this->statusText = false === $text ? '' : (null === $text ? $this->statusTexts[$this->statusCode] : $text);
    }

    /**
     * @param string $name
     * @param mixed $value
     */
    public function parameter($name, $value) {
        $this->parameters[$name] = $value;
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
    public function body($format = 'json') {
        switch ($format) {
            case 'json':
                return $this->parameters ? json_encode($this->parameters) : '';
            case 'xml':
                // this only works for single-level arrays
                $xml = new \SimpleXMLElement('<response/>');
                foreach ($this->parameters as $key => $param) {
                    $xml->addChild($key, $param);
                }

                return $xml->asXML();
        }

        throw new InvalidArgumentException(sprintf('The format %s is not supported', $format));

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
                $this->header('Content-Type', 'application/json');
                break;
            case 'xml':
                $this->header('Content-Type', 'text/xml');
                break;
        }
        // status
        header(sprintf('HTTP/%s %s %s', $this->version, $this->statusCode, $this->statusText));

        foreach ($this->httpHeaders as $name => $header) {
            header(sprintf('%s: %s', $name, $header));
        }
        echo $this->body($format);
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

        $httpHeaders = [
            'Cache-Control' => 'no-store'
        ];

        $this->setStatusCode($statusCode);
        $this->addParameters($parameters);
        $this->addHttpHeaders($httpHeaders);

        if (!$this->isClientError() && !$this->isServerError()) {
            throw new InvalidArgumentException(sprintf('The HTTP status code is not an error ("%s" given).', $statusCode));
        }
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
    public function redirect($url, $statusCode, $state = null, $error = null, $errorDescription = null, $errorUri = null) {
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