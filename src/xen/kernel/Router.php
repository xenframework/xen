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

use xen\kernel\exception\MalFormedRouteException;
use xen\kernel\exception\NoRouteFoundException;

/**
 * Class Router
 *
 * The router has 2 functions:
 *
 *      1. Route            => returns the Controller, the Action and the Params for the current Request
 *      2. Url generator    => The router generates urls automatically
 *
 * @package    xenframework
 * @subpackage xen\kernel
 * @author     Ismael Trascastro <itrascastro@xenframework.com>
 * @copyright  Copyright (c) xenFramework. (http://xenframework.com)
 * @license    MIT License - http://en.wikipedia.org/wiki/MIT_License
 * @link       https://github.com/xenframework/xen
 * @since      Class available since Release 1.0.0
 */
class Router
{
    /**
     * @var array The routes from all active packages
     */
    private $_routes;

    /**
     * @var array The parsed routes with constraints as a regular expressions
     */
    private $_parsedRoutes;

    /**
     * @var array The active packages
     */
    private $_packages;

    /**
     * __construct
     *
     * Loads the routes from the packages
     * Parses the routes
     */
    public function __construct($_packages)
    {
        $this->_packages = $_packages;
        $this->_loadRoutesFromPackages();
        $this->_parsedRoutes = $this->_parseRoutes();
    }

    /**
     * _loadRoutesFromPackages
     *
     * Loads the routes from the packages routes configuration
     *
     */
    private function _loadRoutesFromPackages()
    {
        $this->_routes = array();

        foreach ($this->_packages as $package)
        {
            $packagePath    = 'application/packages/' . str_replace('\\', DIRECTORY_SEPARATOR, $package);
            $routes         = require $packagePath . DIRECTORY_SEPARATOR . 'configs/routes.php';

            foreach ($routes as $route => $value)
            {
                $value['package']       = $package;
                $this->_routes[$route]  = $value;
            }
        }
    }

    /**
     * @param mixed $packages
     */
    public function setPackages($packages)
    {
        $this->_packages = $packages;
    }

    /**
     * @return mixed
     */
    public function getPackages()
    {
        return $this->_packages;
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
     * match
     *
     * After parsing routes the only left key to add is 'params' and it is done right now
     *
     * @param string $url
     *
     * @return mixed The route if match | False if not match found
     */
    public function match($url)
    {
        foreach ($this->_parsedRoutes as $value)
        {
            if (preg_match('!^' . $value['path'] . '$!', $url, $result) == 1)
            {
                $paramsWithValues = array();

                foreach ($value['params'] as $param)
                {
                    $paramsWithValues[$param] = $result[$param];
                }

                $value['params'] = $paramsWithValues;

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
     * Checks all routes against the routeName and checks if params match the given constraints
     *
     * If a route is found then the params are set to that route
     *
     * @param string $routeName
     * @param array $params
     *
     * @throws exception\NoRouteFoundException
     * @internal param string $controller
     * @internal param string $action
     * @return string The url associated to that controller, action and params.
     */
    public function toUrl($routeName, $params = array())
    {
        foreach ($this->_routes as $route => $value)
        {
            $constraints = (array_key_exists('constraints', $value)) ? $value['constraints'] : array();

            if ($routeName == $route && $this->_hasParams($value['path'], $params, $constraints))
            {
                return $this->_setParamsToRoute($value['path'], $params);
            }
        }

        throw new NoRouteFoundException('Route ' . $routeName . ' not found.');
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
        foreach ($params as $key => $value)
        {
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
     * @return bool True if the params match the constraints
     */
    private function _hasParams($route, $params, $constraints)
    {
        foreach ($params as $key => $value)
        {
            if ( strpos(preg_replace('/\s+/', '', $route), '{' . $key . '}') === false ||
                (array_key_exists($key, $constraints) &&
                    preg_match('!' . preg_replace('/\s+/', '', $constraints[$key]) . '!', $value) == 0)
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
     *      - for each param name, replaces it by its constraints regular expression
     *      - sets the param names in the parsed route
     *      - escapes '!' in the parsed route
     *
     * @throws exception\MalFormedRouteException
     * @return array The parsed routes
     */
    private function _parseRoutes()
    {
        $parsedRoutes = array();

        foreach ($this->_routes as $route => $routeValue)
        {
            if (!isset($routeValue['path']) || !isset($routeValue['controller']) || !isset($routeValue['action']))
                throw new MalFormedRouteException($route . ' Malformed route. Be sure you set the path, the controller
                                                and the action sections in your routes definition');

            $pattern = preg_replace('/\s+/', '', $routeValue['path']);

            $paramNames = $this->_getParamNamesFromRoute($routeValue['path']);

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

            $namespace      = (isset($routeValue['namespace'])) ? $routeValue['namespace'] : 'controllers';
            $allow          = (isset($routeValue['allow'])) ? $routeValue['allow'] : array();

            $cacheExpires   = (isset($routeValue['cache']['expires'])) ? $routeValue['cache']['expires'] : 0;
            $cacheRoles     = (isset($routeValue['cache']['roles'])) ? $routeValue['cache']['roles'] : array();
            $cache          = array('expires' => $cacheExpires, 'roles' => $cacheRoles);

            $pattern        = str_replace('!', '\!', $pattern);

            $parsedRoute = array(
                'package'       => $routeValue['package'],
                'path'          => $pattern,
                'namespace'     => $namespace,
                'controller'    => $routeValue['controller'],
                'action'        => $routeValue['action'],
                'params'        => $paramNames,
                'allow'         => $allow,
                'cache'         => $cache,
            );

            $parsedRoutes[$route] = $parsedRoute;
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
