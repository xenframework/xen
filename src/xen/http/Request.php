<?php
/**
 * xenFramework (http://xenframework.com/)
 *
 * This file is part of the xenframework package.
 *
 * (c) Ismael Trascastro <itrascastro@xenframework.com>
 *
 * @link        http://github.com/xenframework for the canonical source repository
 * @copyright   Copyright (c) xenFramework. (http://xenframework.com)
 * @license     MIT License - http://en.wikipedia.org/wiki/MIT_License
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace xen\http;

class Request
{
    private $_url;
    private $_controller;
    private $_action;
    private $_params;
    private $_get;
    private $_post;
    private $_files;
    private $_server;
    private $_env;

    function __construct($_get, $_post, $_files, $_server, $_env)
    {
        $this->_get     = $_get;
        $this->_post    = $_post;
        $this->_files   = $_files;
        $this->_server  = $_server;
        $this->_env     = $_env;
    }

    public static function createFromGlobals()
    {
        return new static($_GET, $_POST, $_FILES, $_SERVER, $_ENV);
    }

    public function get($name = '')
    {
        if ($name == '') {

            return $this->_get;
        }

        return (isset($_GET[$name])) ? $_GET[$name] : null;
    }

    public function post($name = '')
    {
        if ($name == '') {

            return $this->_post;
        }

        return (isset($_POST[$name])) ? $_POST[$name] : null;
    }

    public function getHeaders()
    {
        return getallheaders();
    }

    public function getMethod()
    {
        return $_SERVER['REQUEST_METHOD'];
    }

    public function isGet()
    {
        return $_SERVER['REQUEST_METHOD'] == 'GET';
    }

    public function isPost()
    {
        return $_SERVER['REQUEST_METHOD'] == 'POST';
    }

    public function isPut()
    {
        return $_SERVER['REQUEST_METHOD'] == 'PUT';
    }

    public function isDelete()
    {
        return $_SERVER['REQUEST_METHOD'] == 'DELETE';
    }

    public function isHead()
    {
        return $_SERVER['REQUEST_METHOD'] == 'HEAD';
    }

    public function isOptions()
    {
        return $_SERVER['REQUEST_METHOD'] == 'OPTIONS';
    }

    public function isAjax()
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    public function hasFiles()
    {
        return !empty($_FILES);
    }

    public function getFiles()
    {
        return $_FILES;
    }

    public function server($key)
    {
        return (isset($_SERVER[$key])) ? $_SERVER[$key] : null;
    }

    public function env($key)
    {
        return (isset($_ENV[$key])) ? $_ENV[$key] : null;
    }

    /**
     * @param mixed $url
     */
    public function setUrl($_url)
    {
        $this->_url = $_url;
    }

    /**
     * @return mixed
     */
    public function getUrl()
    {
        return $this->_url;
    }

    /**
     * @param mixed $action
     */
    public function setAction($action)
    {
        $this->_action = $action;
    }

    /**
     * @return mixed
     */
    public function getAction()
    {
        return $this->_action;
    }

    /**
     * @param mixed $controller
     */
    public function setController($controller)
    {
        $this->_controller = $controller;
    }

    /**
     * @return mixed
     */
    public function getController()
    {
        return $this->_controller;
    }

    /**
     * @param mixed $params
     */
    public function setParams($params)
    {
        $this->_params = $params;
    }

    /**
     * @return mixed
     */
    public function getParams()
    {
        return $this->_params;
    }

}
