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
        $this->_router      = $_bootstrap->getResource('Router');

    }

    /**
     * run
     *
     * Calls the Router to get the Controller and the Action to manage the Request
     * Then tries to exec the Action and catch the exception if any error takes place
     *
     * Prepares the Controller and the Error Controller injecting their dependencies
     *
     * Throws two events
     *
     *      1. Before calling the Action. So IoC can be done before calling any action in any controller
     *      2. After calling the Action. So IoC can be done after calling any action in any controller
     *
     * An action can render a view or can return the response directly (json response):
     *
     *      1. $content == null ===> $content = ob_get_clean()
     *      2. $content = $controller->$action()
     *
     * @return Response The Response
     */
    public function run()
    {
        $url    = $this->_request->getUrl();
        $role   = $this->_bootstrap->getResource('Role');
        $cache  = $this->_bootstrap->getResource('Cache');

        $this->_response = new Response();
        $this->_bootstrap->addResource('Response', $this->_response);

        if ($route = $this->_router->match($url))
        {
            if (empty($route['allow']) || in_array($role, $route['allow']))
            {
                if ($route['expires'] > 0 && $content = $cache->get($url, $route['expires']))
                {
                    $this->_response->setStatusCode(200);
                }
                else
                {
                    $controllerName         = ucfirst($route['controller']);
                    $controllerClassName    = $route['namespace'] . '\\' . $controllerName . 'Controller';
                    $actionName             = $route['action'];
                    $action                 = $actionName . 'Action';
                    $viewPath = implode('/', array_slice(explode('\\', $route['namespace']), 1));

                    $controller = new $controllerClassName();

                    $controllerParams = $route['params'];

                    $content = $this->_executeTheAction(
                        $controller,
                        $controllerName,
                        $action,
                        $actionName,
                        $viewPath,
                        $controllerParams
                    );

                    if ($route['expires'] > 0) $cache->put($url, $content);

                    if (!$this->_response->getStatusCode()) $this->_response->setStatusCode(200);
                }
            }
            else
            {
                $controller         = new ErrorController();
                $controllerName     = 'Error';
                $action             = 'forbiddenAction';
                $actionName         = 'forbidden';

                $controllerParams = array(
                    'controller'    => $route['controller'],
                    'action'        => $route['action'],
                );

                $this->_response->setStatusCode(403);

                $content = $this->_executeTheAction($controller, $controllerName, $action, $actionName, '', $controllerParams);
            }
        }
        else
        {
            $controller         = new ErrorController();
            $controllerName     = 'Error';
            $action             = 'pageNotFoundAction';
            $actionName         = 'pageNotFound';

            $controllerParams = array('url' => $url);

            $this->_response->setStatusCode(404);

            $content = $this->_executeTheAction(
                $controller,
                $controllerName,
                $action,
                $actionName,
                '',
                $controllerParams
            );
        }

        $this->_response->setContent($content);

        return $this->_response->send();

    }

    private function _executeTheAction($controller, $controllerName, $action, $actionName, $viewPath, $controllerParams)
    {
        $controller->setParams($controllerParams);

        $this->_bootstrap->bootstrap();

        $this->_eventSystem = $this->_bootstrap->getResource('EventSystem');

        $this->_bootstrap->resolveController(
            $controller,
            $controllerName,
            $actionName,
            $viewPath
        );

        $controller->init();

        $this->_setErrorController();

        ob_start();

        try
        {
            $this->_eventSystem->raiseEvent('PreDispatch', array('controller' => $controller));

            $content = $controller->$action();

            $this->_eventSystem->raiseEvent('PostDispatch', array('controller' => $controller));

        }
        catch (\Exception $e)
        {
            while (ob_get_contents()) ob_end_clean(); //cleaning and closing all nested open buffers
            
            ob_start();

            $content = $this->_exceptionHandler($e);
        }

        if (!isset($content)) $content = ob_get_clean();

        return $content;
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
        $this->_statusCode = 500;

        return $this->_errorController->$action();
    }

    /**
     * _setErrorController
     *
     * Creates and prepares (calling the Bootstrap resolveController) the ErrorController injecting their dependencies
     * and its params
     *
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
            '',
            $itIsTheErrorController
        );

        set_exception_handler(array($this->_errorController, $action));
    }

}
