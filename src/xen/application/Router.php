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
use xen\application\exception\MalFormedRouteException;
use xen\application\exception\NoRouteFoundException;

/**
 * Class Router
 *
 * The router has 3 functions:
 *
 *      1. Route            => returns the Controller, the Action and the Params for the current Request
 *      2. ACL              => The router checks if a Role can access to the current route
 *      3. Url generator    => The router generates urls from a given Controller, Action and Params
 *
 * @package    xenframework
 * @subpackage xen\application
 * @author     Ismael Trascastro <itrascastro@xenframework.com>
 * @copyright  Copyright (c) xenFramework. (http://xenframework.com)
 * @license    MIT License - http://en.wikipedia.org/wiki/MIT_License
 * @link       https://github.com/xenframework/xen
 * @since      Class available since Release 1.0.0
 */
class Router
{
    /**
     * @var string The url of the current Request
     */
    private $_url;

    /**
     * @var array The defined routes in 'application/configs/routes.php'
     */
    private $_routes;

    /**
     * @var array The parsed routes with constraints as a regular expressions
     */
    private $_parsedRoutes;

    /**
     * @var mixed The controller for manage the Request
     */
    private $_controller;

    /**
     * @var string The action for manage the Request
     */
    private $_action;

    /**
     * @var array The params of the current Request
     */
    private $_params;

    /**
     * __construct
     *
     * Filters the url
     * Load the routes from 'application/configs/routes.php'
     * Parse the routes
     */
    public function __construct()
    {
        $this->_routes          = require str_replace('/', DIRECTORY_SEPARATOR, 'application/configs/routes.php');
        $this->_parsedRoutes    = $this->_parseRoutes();
        $this->_params          = array();
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
     * @param mixed $routes
     */
    public function setRoutes($routes)
    {
        $this->_routes = $routes;
    }

    /**
     * @return mixed
     */
    public function getRoutes()
    {
        return $this->_routes;
    }

    /**
     * @param mixed $url
     */
    public function setUrl($url)
    {
        $this->_url = $url;
    }

    /**
     * @return mixed
     */
    public function getUrl()
    {
        return $this->_url;
    }

    /**
     * @param array $params
     */
    public function setParams($params)
    {
        $this->_params = $params;
    }

    /**
     * @return array
     */
    public function getParams()
    {
        return $this->_params;
    }

    public function match($url)
    {
        foreach ($this->_parsedRoutes as $route => $value)
        {
            if (preg_match('!^' . $route . '$!', $url, $params) == 1)
            {
                $value['params'] = $params;

                return $value;
            }
        }

        return false;
    }

    /**
     * toUrl
     *
     * Generates an url using the routes
     *
     * Checks all routes against $controller, $action, $params to find one that has
     * the same controller, same action and matches the constraints
     *
     * If a route is found then the params are set to that route
     *
     * @param string $controller
     * @param string $action
     * @param array  $params
     *
     * @throws exception\NoRouteFoundException
     * @return string The url associated to that controller, action and params.
     */
    public function toUrl($controller, $action, $params = array())
    {
        foreach ($this->_routes as $route => $value) {

            if (array_key_exists('constraints', $value)) {

                $constraints = $value['constraints'];

            } else {

                $constraints = array();
            }

            if ($value['controller'] == $controller &&
                $value['action'] == $action &&
                $this->_hasParams($route, $params, $constraints)
            ) {
                return $this->_setParamsToRoute($route, $params);
            }
        }

        throw new NoRouteFoundException('There is no route associated to the controller ' . $controller .
                                        ', the action ' . $action . ' and the given params');
    }

    /**
     * _setParamsToRoute
     *
     * Sets the params in a route
     * searches for {$paramName} and replaces it by the value stored in $params for that key
     *
     * @param string    $route The matched route
     * @param array     $params The params to be set in that route
     *
     * @return string
     */
    private function _setParamsToRoute($route, $params)
    {
        foreach ($params as $key => $value) {

            $route = preg_replace('/\{' . $key . '\}/', $value, $route);
        }

        return $route;
    }

    /**
     * _hasParams
     *
     * Checks if a param exists in a route and if this param matches the constraints defined in that route
     *
     * @param string    $route
     * @param array     $params
     * @param array     $constraints
     *
     * @return bool
     */
    private function _hasParams($route, $params, $constraints)
    {
        foreach ($params as $key => $value) {

            if (strpos(preg_replace('/\s+/', '', $route), '{' . $key . '}') === false ||
               (
                   array_key_exists($key, $constraints) &&
                   preg_match('!' . preg_replace('/\s+/', '', $constraints[$key]) . '!', $value) == 0
               )
            )
                return false;
        }

        return true;
    }

    /**
     * _parseRoutes
     *
     * Replaces the routes params by the regular expression defined in the constraints in that route
     *
     * To do that, for each route:
     *
     *      - removes white spaces from the route
     *      - gets route param names
     *      - for each param name, replaces it by its constraints
     *      - sets the param names in the parsed route
     *      - escapes '!' in the parsed route
     *
     * @throws exception\MalFormedRouteException
     * @return array The parsed routes
     */
    private function _parseRoutes()
    {
        $parsedRoutes = array();

        foreach ($this->_routes as $route => $routeValue) {

            $pattern = preg_replace('/\s+/', '', $route);

            $paramNames = $this->_getParamNamesFromRoute($route);

            foreach ($paramNames as $paramName) {

                if (isset($routeValue['constraints'][$paramName])) {

                    $constraint = '(?P<' . $paramName . '>' . $routeValue['constraints'][$paramName] . ')';
                    $constraint = preg_replace('/\s+/', '', $constraint);
                    $pattern = str_replace('{' . $paramName . '}', $constraint, $pattern);

                } else {

                    $constraint = '(?P<' . $paramName . '>\S+)';
                    $pattern = str_replace('{' . $paramName . '}', $constraint, $pattern);
                }
            }

            if (!isset($routeValue['controller']) || !isset($routeValue['action']) || !isset($routeValue['allow']))
                throw new MalFormedRouteException($route . ' Malformed route. Be sure you set the controller,
                                                the action and the allow sections in your routes definition');

            $expires = (isset($routeValue['expires'])) ? $routeValue['expires'] : 0;

            $parsedRoute = array(
                'controller'    => $routeValue['controller'],
                'action'        => $routeValue['action'],
                'params'        => $paramNames,
                'allow'         => $routeValue['allow'],
                'expires'       => $expires,
            );

            $pattern = str_replace('!', '\!', $pattern);
            $parsedRoutes[$pattern] = $parsedRoute;
        }

        return $parsedRoutes;
    }

    /**
     * _getParamNamesFromRoute
     *
     * Returns the param names in a route
     *
     * @param string $route To extract from the param names
     *
     * @return array The param names
     */
    private function _getParamNamesFromRoute($route)
    {
        $param = '{(.+?)}';
        preg_match_all('!' . $param . '!', $route, $results);

        return $results[1];
    }
}