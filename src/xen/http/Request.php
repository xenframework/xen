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

/**
 * Class Request
 *
 * This class allows to decouple the code from the super globals
 *
 * @package    xenframework
 * @subpackage xen\http
 * @author     Ismael Trascastro <itrascastro@xenframework.com>
 * @copyright  Copyright (c) xenFramework. (http://xenframework.com)
 * @license    MIT License - http://en.wikipedia.org/wiki/MIT_License
 * @link       https://github.com/xenframework/xen
 * @since      Class available since Release 1.0.0
 */
class Request
{
    /**
     * @var string The requested url
     */
    private $_url;

    /**
     * @var string The controller name
     */
    private $_controller;

    /**
     * @var string The action name
     */
    private $_action;

    /**
     * @var array The action params for this request
     */
    private $_params;

    /**
     * @var array SuperGlobal $_GET
     */
    private $_get;

    /**
     * @var array SuperGlobal $_POST
     */
    private $_post;

    /**
     * @var array SuperGlobal $_FILES
     */
    private $_files;

    /**
     * @var array SuperGlobal $_SERVER
     */
    private $_server;

    /**
     * @var array SuperGlobal $_ENV
     */
    private $_env;

    /**
     * __construct
     *
     * @param array $_get
     * @param array $_post
     * @param array $_files
     * @param array $_server
     * @param array $_env
     */
    function __construct($_get, $_post, $_files, $_server, $_env)
    {
        $this->_get     = $_get;
        $this->_post    = $_post;
        $this->_files   = $_files;
        $this->_server  = $_server;
        $this->_env     = $_env;
    }

    /**
     * createFromGlobals
     *
     * Creates a new Request from SuperGlobals
     *
     * @return static
     */
    public static function createFromGlobals()
    {
        return new static($_GET, $_POST, $_FILES, $_SERVER, $_ENV);
    }

    /**
     * get
     *
     * Allow access to $_GET SuperGlobal
     *
     * @param string $name
     *
     * @return array|null
     */
    public function get($name = '')
    {
        if ($name == '') return $this->_get;

        return (isset($_GET[$name])) ? $_GET[$name] : null;
    }

    /**
     * post
     *
     * Allow access to $_POST SuperGlobal
     *
     * @param string $name
     *
     * @return array|null
     */
    public function post($name = '')
    {
        if ($name == '') return $this->_post;

        return (isset($_POST[$name])) ? $_POST[$name] : null;
    }

    /**
     * getHeaders
     *
     * The Headers
     *
     * @return mixed
     */
    public function getHeaders()
    {
        return getallheaders();
    }

    /**
     * getMethod
     *
     * The REQUEST_METHOD
     *
     * @return string
     */
    public function getMethod()
    {
        return $_SERVER['REQUEST_METHOD'];
    }

    /**
     * isGet
     *
     * If REQUEST_METHOD is GET
     *
     * @return bool
     */
    public function isGet()
    {
        return $_SERVER['REQUEST_METHOD'] == 'GET';
    }

    /**
     * isPost
     *
     * If REQUEST_METHOD is POST
     *
     * @return bool
     */
    public function isPost()
    {
        return $_SERVER['REQUEST_METHOD'] == 'POST';
    }

    /**
     * isPut
     *
     * If REQUEST_METHOD is PUT
     *
     * @return bool
     */
    public function isPut()
    {
        return $_SERVER['REQUEST_METHOD'] == 'PUT';
    }

    /**
     * isDelete
     *
     * If REQUEST_METHOD is DELETE
     *
     * @return bool
     */
    public function isDelete()
    {
        return $_SERVER['REQUEST_METHOD'] == 'DELETE';
    }

    /**
     * isHead
     *
     * If REQUEST_METHOD is HEAD
     *
     * @return bool
     */
    public function isHead()
    {
        return $_SERVER['REQUEST_METHOD'] == 'HEAD';
    }

    /**
     * isOptions
     *
     * If REQUEST_METHOD is OPTIONS
     *
     * @return bool
     */
    public function isOptions()
    {
        return $_SERVER['REQUEST_METHOD'] == 'OPTIONS';
    }

    /**
     * isAjax
     *
     * If is Ajax
     *
     * @return bool
     */
    public function isAjax()
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * hasFiles
     *
     * If $_FILES is not empty
     *
     * @return bool
     */
    public function hasFiles()
    {
        return !empty($_FILES);
    }

    /**
     * getFiles
     *
     * $_FILES
     *
     * @return mixed
     */
    public function getFiles()
    {
        return $_FILES;
    }

    /**
     * server
     *
     * The $_SERVER value for a given key
     *
     * @param string $key
     *
     * @return string|null
     */
    public function server($key)
    {
        return (isset($_SERVER[$key])) ? $_SERVER[$key] : null;
    }

    /**
     * env
     *
     * The $_ENV value for a given key
     *
     * @param $key
     *
     * @return string|null
     */
    public function env($key)
    {
        return (isset($_ENV[$key])) ? $_ENV[$key] : null;
    }

    /**
     * setUrl
     *
     * @param string $_url
     */
    public function setUrl($_url)
    {
        $this->_url = $_url;
    }

    /**
     * getUrl
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->_url;
    }

    /**
     * setAction
     *
     * @param string $action
     */
    public function setAction($action)
    {
        $this->_action = $action;
    }

    /**
     * getAction
     *
     * @return string
     */
    public function getAction()
    {
        return $this->_action;
    }

    /**
     * setController
     *
     * @param string $controller
     */
    public function setController($controller)
    {
        $this->_controller = $controller;
    }

    /**
     * getController
     *
     * @return string
     */
    public function getController()
    {
        return $this->_controller;
    }

    /**
     * setParams
     *
     * @param array $params
     */
    public function setParams($params)
    {
        $this->_params = $params;
    }

    /**
     * getParams
     *
     * @return array
     */
    public function getParams()
    {
        return $this->_params;
    }

}
