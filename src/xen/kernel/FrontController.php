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

namespace xen\kernel;

use main\Bootstrap;
use xen\eventSystem\EventSystem;
use xen\http\Request;
use xen\http\Response;

/**
 * Class FrontController
 *
 * Selects the Controller and the Action to manage the Request. It also resolves the controller dependencies
 *
 * @package    xenframework
 * @subpackage xen\kernel
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
     * @var Dic From the Front Controller is used as a Service Locator. In the controllers as a DiC
     */
    private $_container;

    /**
     * @var mixed Running Package bootstrap
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
     * @var object The controller who manages the Error
     */
    private $_errorController;

    /**
     * @var Response The Response
     */
    private $_response;

    /**
     * @var EventSystem Used to raise events in the FrontController
     */
    private $_eventSystem;

    /**
     * __construct
     *
     * Gets the Request from the Bootstrap
     *
     * @param Dic $_container
     */
    public function __construct(Dic $_container)
    {
        $this->_container   = $_container;
        $this->_request     = $_container->getResource('Request');
        $this->_router      = $_container->getResource('Router');

        $this->_router->setPackages($this->_container->getResource('Packages'));

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
        $cache  = $this->_container->getResource('Cache');

        $this->_response = new Response();
        $this->_container->addResource('Response', $this->_response);

        if ($route = $this->_router->match($url))
        {
            $bootstrapClassName = $route['package'] . '\\' . 'Bootstrap';
            $this->_bootstrap = new $bootstrapClassName($route['package']);
            $this->_bootstrap->setContainer($this->_container);
            $this->_bootstrap->minimalBootstrap();

            $role   = $this->_container->getResource('Role');

            if (empty($route['allow']) || in_array($role, $route['allow']))
            {
                if ($route['cache']['expires'] > 0 && (empty($route['cache']['roles']) || in_array($role, $route['cache']['roles'])) && $content = $cache->get($url, $route['cache']['expires']))
                {
                    $this->_response->setStatusCode(200);
                }
                else
                {
                    $package                = $route['package'];
                    $namespace              = $route['namespace'];
                    $controllerName         = ucfirst($route['controller']);
                    $controllerClassName    = $route['package'] . '\\' . $route['namespace'] . '\\' . $controllerName . 'Controller';
                    $actionName             = $route['action'];
                    $action                 = $actionName . 'Action';
                    $viewPath               = implode('/', array_slice(explode('\\', $route['namespace']), 1));
                    $controller             = new $controllerClassName();
                    $controllerParams       = $route['params'];

                    $content = $this->_executeTheAction(
                        $package,
                        $namespace,
                        $controller,
                        $controllerName,
                        $action,
                        $actionName,
                        $viewPath,
                        $controllerParams
                    );

                    if ($route['cache']['expires'] > 0) $cache->put($url, $content);

                    if (!$this->_response->getStatusCode()) $this->_response->setStatusCode(200);
                }
            }
            else
            {
                $namespace              = 'controllers';
                $controllerClassName    = 'main\\controllers\\ErrorController';
                $controller             = new $controllerClassName();
                $controllerName         = 'Error';
                $action                 = 'forbiddenAction';
                $actionName             = 'forbidden';

                $controllerParams = array(
                    'route'    => $url,
                );

                $this->_response->setStatusCode(403);

                $content = $this->_executeTheAction('main', $namespace, $controller, $controllerName, $action, $actionName, '', $controllerParams);
            }
        }
        else
        {
            $this->_bootstrap = new Bootstrap('main');
            $this->_bootstrap->setContainer($this->_container);

            $namespace              = 'controllers';
            $controllerClassName    = 'main\\controllers\\ErrorController';
            $controller             = new $controllerClassName();
            $controllerName         = 'Error';
            $action                 = 'pageNotFoundAction';
            $actionName             = 'pageNotFound';
            $controllerParams       = array('url' => $url);

            $this->_response->setStatusCode(404);

            $content = $this->_executeTheAction(
                'main',
                $namespace,
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

    private function _executeTheAction($package, $namespace, $controller, $controllerName, $action, $actionName, $viewPath, $controllerParams)
    {
        $controller->setParams($controllerParams);

        $this->_container->addResource('Package', $package);

        $this->_bootstrap->bootstrap();

        $this->_eventSystem = $this->_container->getResource('EventSystem');
        $this->_eventSystem->setPackage($package);

        $this->_container->resolveController(
            $package,
            $namespace,
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

        if (!isset($content)) $content = ob_get_contents();

        ob_end_clean();

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
        $this->_response->setStatusCode(500);

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
        $errorClassName = 'main\\controllers\\ErrorController';
        $this->_errorController = new $errorClassName();
        $action = FrontController::EXCEPTION_HANDLER_ACTION . 'Action';

        $itIsTheErrorController     = true;
        $controllerName             = 'error';

        $this->_container->resolveController(
            'main',
            'controllers',
            $this->_errorController,
            $controllerName,
            FrontController::EXCEPTION_HANDLER_ACTION,
            '',
            $itIsTheErrorController
        );

        set_exception_handler(array($this->_errorController, $action));
    }

}
