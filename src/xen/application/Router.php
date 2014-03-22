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
     *
     * @param string $_url The url of the current Request
     */
    public function __construct($_url)
    {
        $this->_cleanUrl($_url);

        $this->_routes          = require str_replace('/', DIRECTORY_SEPARATOR, 'application/configs/routes.php');
        $this->_parsedRoutes    = $this->_parseRoutes();
        $this->_params          = array();
    }

    /**
     * _cleanUrl
     *
     * Filters the url and add a start slash to the url
     *
     * @param string $url A route must start with a slash
     */
    private function _cleanUrl($url)
    {
        $this->_url = ($url === null) ? '/' : '/' . filter_var($url, FILTER_SANITIZE_URL);
    }

    /**
     * route
     *
     * Try to match the url with one of the routes
     *
     *      if match        => check if it is allowed for the current role
     *                          if not allowed  => Error controller , forbidden Action
     *                          if allowed      => returns the controller and the action of the matched route
     *      if not match    => Error controller, pageNotFound Action
     *
     * @param string $role The role to be used in ACL
     */
    public function route($role)
    {
        $found = false;

        foreach ($this->_parsedRoutes as $route => $value) {

            if (preg_match('!^' . $route . '$!', $this->_url, $results) == 1) {

                $found = true;

                if (empty($value['allow']) || in_array($role, $value['allow'])) {

                    $params = array();

                    foreach ($value['params'] as $param) {

                        $params[$param] = $results[$param];
                    }

                    $this->_controller  = ucfirst($value['controller']);
                    $this->_action      = $value['action'];
                    $this->_params      = $params;

                } else {

                    $this->_controller  = 'Error';
                    $this->_action      = 'forbidden';
                    $this->_params      = array(
                        'controller'    => $value['controller'],
                        'action'        => $value['action'],
                    );
                }

                break;
            }
        }

        if (!$found) {

            $this->_controller  = 'Error';
            $this->_action      = 'pageNotFound';
            $this->_params      = array('url' => $this->_url);
        }
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
     * @param string    $controller
     * @param string    $action
     * @param array     $params
     *
     * @return bool|string The url associated to that controller, action and params. False if not route is found
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

        return false;
    }

    /**
     * _setParamsToRoute
     *
     * Sets the params in a route
     * searches for {$paramName} and replaces it by the value stored in $params for that key
     *
     * @param $route The matched route
     * @param $params The params to be set in that route
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
     * @param $route
     * @param $params
     * @param $constraints
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
     *
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

            $parsedRoute = array(
                'controller'    => $routeValue['controller'],
                'action'        => $routeValue['action'],
                'params'        => $paramNames,
                'allow'         => $routeValue['allow'],
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
     * Creates groups in the route for each param name
     *
     * Finally removes the first entry in the results array (it is the matched route, but not a param name)
     *
     * @param string $route To extract from the param names
     *
     * @return array The param names
     */
    private function _getParamNamesFromRoute($route)
    {
        $regex = str_replace('{', '{(', $route);
        $regex = str_replace('}', ')}', $regex);

        preg_match('!^' . $regex . '$!', $route, $results);

        unset($results[0]);

        return array_values($results);
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

}