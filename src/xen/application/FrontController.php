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

namespace xen\application;

use bootstrap\Bootstrap;
use controllers\ErrorController;
use xen\eventSystem\Event;
use xen\eventSystem\EventSystem;
use xen\http\Request;
use xen\http\Response;

/**
 * Class FrontController
 *
 * Selects the Controller and the Action to manage the Request. It also resolves the controller dependencies
 *
 * @package    xenframework
 * @subpackage xen\application
 * @author     Ismael Trascastro <itrascastro@xenframework.com>
 * @copyright  Copyright (c) xenFramework. (http://xenframework.com)
 * @license    MIT License - http://en.wikipedia.org/wiki/MIT_License
 * @link       https://github.com/xenframework/xen
 * @since      Class available since Release 1.0.0
 */
class FrontController
{
    /**
     * @const EXCEPTION_HANDLER_ACTION The ErrorController Action to be called when an exception takes place
     */
    const EXCEPTION_HANDLER_ACTION = 'exceptionHandler';

    /**
     * @var \bootstrap\Bootstrap From here is used as a Service Locator
     */
    private $_bootstrap;

    /**
     * @var Request The Request object from Bootstrap
     */
    private $_request;

    /**
     * @var Router The Router
     */
    private $_router;

    /**
     * @var object The controller who handles the Request. It is returned by the router
     */
    private $_controller;

    /**
     * @var string The action who handles the Request. It is returned by the router
     */
    private $_action;

    /**
     * @var string The output returned by calling the action in the controller
     */
    private $_content;

    /**
     * @var object The controller who manages the Error
     */
    private $_errorController;

    /**
     * @var Response The Response
     */
    private $_response;

    /**
     * @var int The Status Code of the Response
     */
    private $_statusCode;

    /**
     * @var EventSystem Used to raise events in the FrontController
     */
    private $_eventSystem;

    /**
     * __construct
     *
     * Gets the Request from the Bootstrap
     *
     * @param Bootstrap $_bootstrap
     */
    public function __construct(Bootstrap $_bootstrap)
    {
        $this->_bootstrap   = $_bootstrap;
        $this->_request     = $_bootstrap->getResource('Request');
        $this->_eventSystem = $_bootstrap->getResource('EventSystem');
    }

    /**
     * run
     *
     * Calls the Router to get the Controller and the Action to manage the Request
     * Then tries to exec the Action and catch the exception if any error takes place
     *
     * Prepares the Controller and the Error Controller injection their dependencies
     *
     * Throws two events
     *
     *      1. Before calling the Action. So IoC can be done before calling any action in any controller
     *      2. After calling the Action. So IoC can be done after calling any action in any controller
     *
     * @return mixed The Response
     */
    public function run()
    {
        $url = $this->_request->get('url');
        $this->_request->setUrl($url);

        $this->_router = new Router($url);
        $this->_bootstrap->addResource('Router', $this->_router);
        $this->_router->route($this->_bootstrap->getResource('Role'));

        $this->_statusCode = ($this->_router->getAction() != 'PageNotFound') ? 200 : 404;

        $this->_response = new Response();
        $this->_bootstrap->addResource('Response', $this->_response);

        $this->_setController();
        $this->_setErrorController();

        try {

            $this->_raiseEvent('PreDispatch');

            $action = $this->_action;
            $this->_content = $this->_controller->$action();

            $this->_raiseEvent('PostDispatch');

        } catch (\Exception $e) {

            $this->_exceptionHandler($e);
        }

        if (!$this->_response->getStatusCode()) { // It can be set from the action

            $this->_response->setStatusCode($this->_statusCode);
        }

        $this->_response->setContent($this->_content);

        return $this->_response->send();
    }

    /**
     * _raiseEvent
     *
     * Raises an Event
     *
     * @param string $name The event name
     */
    private function _raiseEvent($name)
    {
        $event = new Event($name, array('controller' => $this->_controller));
        $this->_eventSystem->raiseEvent($event);
    }

    /**
     * _exceptionHandler
     *
     * Manages the exception calling the ErrorController Action for this purpose: EXCEPTION_HANDLER_ACTION
     *
     * @param \Exception $e The exception
     */
    private function _exceptionHandler($e)
    {
        $this->_errorController->setParams(array('e' => $e));
        $action = FrontController::EXCEPTION_HANDLER_ACTION . 'Action';
        $this->_content = $this->_errorController->$action();
        $this->_statusCode = 500;
    }

    /**
     * _setErrorController
     *
     * Creates and prepares (calling the Bootstrap resolveController) the ErrorController injecting their dependencies
     * Also set the new Exception Handler for manage all uncaught exceptions in the user application
     */
    private function _setErrorController()
    {
        $this->_errorController = new ErrorController();
        $action = FrontController::EXCEPTION_HANDLER_ACTION . 'Action';

        $itIsTheErrorController = true;
        $controllerName = 'error';

        $this->_bootstrap->resolveController(
            $this->_errorController,
            $controllerName,
            FrontController::EXCEPTION_HANDLER_ACTION,
            $itIsTheErrorController
        );

        set_exception_handler(array($this->_errorController, $action));
    }

    /**
     * _setController
     *
     * Creates and prepares (calling the Bootstrap resolveController) the Controller injecting their dependencies
     *
     * Finally calls init method with all dependencies already injected (In the constructor dependencies are not still
     * injected)
     */
    private function _setController()
    {
        $controller = 'controllers\\' . $this->_router->getController() . 'Controller';
        $this->_action = $this->_router->getAction() . 'Action';

        $this->_controller = new $controller();

        $this->_bootstrap->resolveController(
            $this->_controller,
            $this->_router->getController(),
            $this->_router->getAction()
        );

        $this->_controller->init();
    }
}
