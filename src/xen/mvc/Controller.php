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

class Controller
{
    protected $_view;
    protected $_layout;
    protected $_params;
    protected $_request;
    protected $_response;
    protected $_session;
    protected $_config;
    protected $_actionHelperBroker;
    protected $_eventSystem;
    protected $_appEnv;
    protected $_router;

    public function __construct()
    {
    }

    public function init()
    {
    }

    /**
     * @param mixed $appEnv
     */
    public function setAppEnv($appEnv)
    {
        $this->_appEnv = $appEnv;
    }

    /**
     * @return mixed
     */
    public function getAppEnv()
    {
        return $this->_appEnv;
    }

    /**
     * @param mixed $eventSystem
     */
    public function setEventSystem($eventSystem)
    {
        $this->_eventSystem = $eventSystem;
    }

    /**
     * @return mixed
     */
    public function getEventSystem()
    {
        return $this->_eventSystem;
    }

    /**
     * @param \xen\mvc\helpers\HelperBroker $actionHelperBroker
     */
    public function setActionHelperBroker($actionHelperBroker)
    {
        $this->_actionHelperBroker = $actionHelperBroker;
    }

    /**
     * @return \xen\mvc\helpers\HelperBroker
     */
    public function getActionHelperBroker()
    {
        return $this->_actionHelperBroker;
    }

    /**
     * @param mixed $config
     */
    public function setConfig($config)
    {
        $this->_config = $config;
    }

    /**
     * @return mixed
     */
    public function getConfig()
    {
        return $this->_config;
    }

    /**
     * @param \xen\mvc\view\Phtml $layout
     */
    public function setLayout($layout)
    {
        $this->_layout = $layout;
    }

    /**
     * @return \xen\mvc\view\Phtml
     */
    public function getLayout()
    {
        return $this->_layout;
    }

    /**
     * @param $_params
     *
     * @internal param mixed $params
     */
    public function setParams($_params)
    {
        $this->_params = $_params;
    }

    /**
     * @return mixed
     */
    public function getParams()
    {
        return $this->_params;
    }

    public function getParam($key)
    {
        foreach ($this->_params as $keyword => $value)
        {
            if ($keyword == $key) {
                return $value;
            }
        }
        return null;
    }

    /**
     * @param mixed $view
     */
    public function setView($_view)
    {
        $this->_view = $_view;
        $this->_layout->addPartials(
            array(
                'content' => $this->_view,
            )
        );
    }

    /**
     * @return mixed
     */
    public function getView()
    {
        return $this->_view;
    }

    /**
     * @param mixed $request
     */
    public function setRequest($request)
    {
        $this->_request = $request;
    }

    /**
     * @return mixed
     */
    public function getRequest()
    {
        return $this->_request;
    }

    /**
     * @param mixed $response
     */
    public function setResponse($response)
    {
        $this->_response = $response;
    }

    /**
     * @return mixed
     */
    public function getResponse()
    {
        return $this->_response;
    }

    /**
     * @param mixed $session
     */
    public function setSession($session)
    {
        $this->_session = $session;
    }

    /**
     * @return mixed
     */
    public function getSession()
    {
        return $this->_session;
    }

    /**
     * @param mixed $router
     */
    public function setRouter($router)
    {
        $this->_router = $router;
    }

    /**
     * @return mixed
     */
    public function getRouter()
    {
        return $this->_router;
    }

    /**
     * We extend layout properties to partials
     */
    public function render()
    {
        $this->_layout->render();
    }

    protected function _redirect($controller, $action)
    {
        $url = $this->_router->toUrl($controller, $action);
        header('location:' . $url);
        exit;
    }

    protected function _forward($action)
    {
        $controller = $this->_request->getController();

        $viewPath = str_replace('/', DIRECTORY_SEPARATOR, 'application/views/scripts/');
        $this->_view->setFile($viewPath . $controller . DIRECTORY_SEPARATOR . $action . '.phtml');

        $actionName = $action . 'Action';

        return $this->$actionName();
    }
}
