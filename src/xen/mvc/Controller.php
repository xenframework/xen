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

namespace xen\mvc;

use xen\kernel\Cache;
use xen\mvc\exception\ControllerParamNotFoundException;
use xen\mvc\exception\ControllerRedirectEmptyUrlException;
use xen\mvc\view\Phtml;
use xen\http\Request;
use xen\http\Response;
use xen\http\Session;
use xen\config\Config;
use xen\mvc\helpers\ActionHelperBroker;
use xen\eventSystem\EventSystem;
use xen\kernel\Router;

/**
 * Class Controller
 *
 * Every controller in the application will extend this class
 *
 * The resources available in a controller are:
 *
 *      - View
 *      - Layout
 *      - Params (For the current action)
 *      - Request
 *      - Response
 *      - Session
 *      - Config (The application configure settings)
 *      - ActionHelperBroker
 *      - EventSystem
 *      - AppStage
 *      - Router
 *      - Cache
 *
 * @package    xenframework
 * @subpackage xen\mvc
 * @author     Ismael Trascastro <itrascastro@xenframework.com>
 * @copyright  Copyright (c) xenFramework. (http://xenframework.com)
 * @license    MIT License - http://en.wikipedia.org/wiki/MIT_License
 * @link       https://github.com/xenframework/xen
 * @since      Class available since Release 1.0.0
 */
class Controller
{
    /**
     * @var Phtml
     */
    protected $_view;

    /**
     * @var Phtml
     */
    protected $_layout;

    /**
     * @var array
     */
    private $_params;

    /**
     * @var Request
     */
    protected $_request;

    /**
     * @var Response
     */
    protected $_response;

    /**
     * @var Session
     */
    protected $_session;

    /**
     * @var Config
     */
    protected $_config;

    /**
     * @var ActionHelperBroker;
     */
    protected $_actionHelperBroker;

    /**
     * @var EventSystem
     */
    protected $_eventSystem;

    /**
     * @var string
     */
    protected $_appStage;

    /**
     * @var Router
     */
    protected $_router;

    /**
     * @var Cache
     */
    protected $_cache;

    protected $_package;

    protected $_namespace;

    /**
     * __construct
     *
     * Can be override
     */
    public function __construct()
    {
    }

    /**
     * init
     *
     * Can be override to do staff before any action is called
     *
     */
    public function init()
    {
    }

    /**
     * setAppStage
     *
     * The Application Environment state
     *
     * @param string $appStage
     */
    public function setAppStage($appStage)
    {
        $this->_appStage = $appStage;
    }

    /**
     * getAppStage
     *
     * @return string
     */
    public function getAppStage()
    {
        return $this->_appStage;
    }

    /**
     * setEventSystem
     *
     * @param EventSystem $eventSystem
     */
    public function setEventSystem(EventSystem $eventSystem)
    {
        $this->_eventSystem = $eventSystem;
    }

    /**
     * getEventSystem
     *
     * @return EventSystem
     */
    public function getEventSystem()
    {
        return $this->_eventSystem;
    }

    /**
     * setActionHelperBroker
     *
     * @param ActionHelperBroker $actionHelperBroker
     */
    public function setActionHelperBroker(ActionHelperBroker $actionHelperBroker)
    {
        $this->_actionHelperBroker = $actionHelperBroker;
    }

    /**
     * getActionHelperBroker
     *
     * @return ActionHelperBroker
     */
    public function getActionHelperBroker()
    {
        return $this->_actionHelperBroker;
    }

    /**
     * setConfig
     *
     * @param Config $config
     */
    public function setConfig(Config $config)
    {
        $this->_config = $config;
    }

    /**
     * getConfig
     *
     * @return Config
     */
    public function getConfig()
    {
        return $this->_config;
    }

    /**
     * setLayout
     *
     * @param Phtml $layout
     */
    public function setLayout(Phtml $layout)
    {
        $this->_layout = $layout;
    }

    /**
     * getLayout
     *
     * @return Phtml
     */
    public function getLayout()
    {
        return $this->_layout;
    }

    /**
     * setParams
     *
     * @param array $_params
     */
    public function setParams($_params)
    {
        $this->_params = $_params;
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

    /**
     * getParam
     *
     * @param string $key
     *
     * @throws exception\ControllerParamNotFoundException
     * @return mixed
     */
    public function getParam($key)
    {
        if (array_key_exists($key, $this->_params)) return $this->_params[$key];

        throw new ControllerParamNotFoundException('The param ' . $key . ' does not exist');
    }

    /**
     * setParam
     *
     * @param string $param
     * @param mixed $value
     */
    public function setParam($param, $value)
    {
        $this->_params[$param] = $value;
    }

    /**
     * setView
     *
     * Set the View and add it as a partial to the Layout
     *
     * @param Phtml $_view
     */
    public function setView(Phtml $_view)
    {
        $this->_view = $_view;

        $this->_layout->addPartial('content', $this->_view);
    }

    /**
     * getView
     *
     * @return Phtml
     */
    public function getView()
    {
        return $this->_view;
    }

    /**
     * setRequest
     *
     * @param Request $request
     */
    public function setRequest(Request $request)
    {
        $this->_request = $request;
    }

    /**
     * getRequest
     *
     * @return Request
     */
    public function getRequest()
    {
        return $this->_request;
    }

    /**
     * setResponse
     *
     * @param Response $response
     */
    public function setResponse(Response $response)
    {
        $this->_response = $response;
    }

    /**
     * getResponse
     *
     * @return Response
     */
    public function getResponse()
    {
        return $this->_response;
    }

    /**
     * setSession
     *
     * @param Session $session
     */
    public function setSession(Session $session)
    {
        $this->_session = $session;
    }

    /**
     * getSession
     *
     * @return Session
     */
    public function getSession()
    {
        return $this->_session;
    }

    /**
     * setRouter
     *
     * @param Router $router
     */
    public function setRouter(Router $router)
    {
        $this->_router = $router;
    }

    /**
     * getRouter
     *
     * @return Router
     */
    public function getRouter()
    {
        return $this->_router;
    }

    /**
     * @param \xen\kernel\Cache $cache
     */
    public function setCache($cache)
    {
        $this->_cache = $cache;
    }

    /**
     * @return \xen\kernel\Cache
     */
    public function getCache()
    {
        return $this->_cache;
    }

    /**
     * @param mixed $package
     */
    public function setPackage($package)
    {
        $this->_package = $package;
    }

    /**
     * @return mixed
     */
    public function getPackage()
    {
        return $this->_package;
    }

    /**
     * @param mixed $namespace
     */
    public function setNamespace($namespace)
    {
        $this->_namespace = $namespace;
    }

    /**
     * @return mixed
     */
    public function getNamespace()
    {
        return $this->_namespace;
    }

    /**
     * render
     *
     * Calls layout render method
     */
    public function render()
    {
        $this->_layout->render();
    }

    /**
     * _redirect
     *
     * Redirects to an url
     *
     * @param string $url
     * @param int $httpStatusCode
     *
     * @throws exception\ControllerRedirectEmptyUrlException
     */
    protected function _redirect($url, $httpStatusCode = 302)
    {
        if (empty($url)) throw new ControllerRedirectEmptyUrlException('Cannot redirect to an empty url.');

        header('location:' . $url, true, $httpStatusCode);
        exit;
    }

    /**
     * _redirectInSeconds
     *
     * Redirects after given seconds
     *
     * @param string $url
     * @param int $seconds
     *
     * @throws exception\ControllerRedirectEmptyUrlException
     */
    protected function _redirectInSeconds($url, $seconds = 0)
    {
        if (empty($url)) throw new ControllerRedirectEmptyUrlException('Cannot redirect to an empty url.');

        header('refresh:' . $seconds . ';url=' . $url);
    }

    /**
     * _forward
     *
     * Calls to another action in the same controller
     * Updates the view pointing to the new action to enable its execution
     *
     * @param string $action
     *
     * @return mixed
     */
    protected function _forward($action)
    {
        $controller = $this->_request->getController();

        $viewPath = 'application/packages/' . str_replace('\\', DIRECTORY_SEPARATOR, $this->_package) . '/views/scripts/' . implode('/', array_slice(explode('\\', $this->_namespace), 1)) . '/';
        $this->_view->setFile($viewPath . $controller . DIRECTORY_SEPARATOR . $action . '.phtml');

        $actionName = $action . 'Action';

        return $this->$actionName();
    }
}
