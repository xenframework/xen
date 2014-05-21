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

use xen\config\Config;
use xen\db\Adapter;
use xen\db\doctrine\DoctrineBootstrap;
use xen\http\Session;
use xen\kernel\bootstrap\exception\ContainerDependencyDatabaseNotFoundException;
use xen\kernel\bootstrap\exception\ContainerResourceNotFoundException;
use xen\mvc\view\Phtml;

/**
 * Class Dic
 *
 * The dependency injection container
 *
 * @package    xenframework
 * @subpackage xen\kernel
 * @author     Ismael Trascastro <itrascastro@xenframework.com>
 * @copyright  Copyright (c) xenFramework. (http://xenframework.com)
 * @license    MIT License - http://en.wikipedia.org/wiki/MIT_License
 * @link       https://github.com/xenframework/xen
 * @since      Class available since Release 1.0.0
 */
class Dic
{
    /**
     * @var array The container implementation
     */
    private $_resources;

    /**
     * __construct
     *
     * Creates an empty container
     */
    public function __construct()
    {
        $this->_resources = array();
    }

    /**
     * _initTheContainer
     *
     * Initializes the container with the minimum resources needed for a cached response
     *
     *      - Session
     *      - Role
     *      - Cache
     *      - Router
     *
     */
    public function _initTheContainer()
    {
        $this->addResource('Session', new Session());
        $this->addResource('Role', 'guest');
        $this->addResource('Cache', new Cache('application/cache'));

        $router = new Router($this->getResource('Packages'));
        $this->addResource('Router', $router);
    }

    /**
     * addResource
     *
     * Stores $resource in the container set to $value
     *
     * @param string $resource
     * @param mixed $value
     */
    public function addResource($resource, $value)
    {
        $this->_resources[$resource] = $value;
    }

    /**
     * getResource
     *
     * If the resource exists in the container then it is returned otherwise null
     *
     * @param string $resource identifier of the resource
     *
     * @throws bootstrap\exception\ContainerResourceNotFoundException
     * @return mixed The resource
     */
    public function getResource($resource)
    {
        if ($this->resourceExists($resource)) return $this->_resources[$resource];

        throw new ContainerResourceNotFoundException('Resource ' . $resource . ' does not exist in the Container');
    }

    /**
     * resourceExists
     *
     * @param string $resource
     *
     * @return bool
     */
    public function resourceExists($resource)
    {
        return array_key_exists($resource, $this->_resources);
    }

    /**
     * _dependencyDatabase
     *
     * Load the database config 'application/configs/databases.php'
     *
     * Can exists more than one database:
     *
     *      The first time 'application/configs/databases.php' is loaded into Databases resource
     *
     *      Each Database resource is named as follows: Database_ID_Orm
     *
     *      Orm can be:
     *
     *          - pdo
     *          - doctrine
     *
     *      So when this method is called with $db ID, it creates the Database_$db_$orm resource
     *      and stores it in the container
     *
     *
     * @param string $db The ID of the database
     *
     * @param string $orm
     *
     * @throws bootstrap\exception\ContainerDependencyDatabaseNotFoundException
     */
    protected function _dependencyDatabase($db, $orm)
    {
        if (!$this->resourceExists('Databases')) {

            $databases = require 'application/configs/databases.php';
            $this->addResource('Databases', $databases);
        }

        if (!array_key_exists($db, $this->getResource('Databases')))
            throw new ContainerDependencyDatabaseNotFoundException('Dependency database ' . $db . ' not found in ' . '"application/configs/databases.php"');

        $databasesResource  = $this->getResource('Databases');
        $dbConfig           = new Config($databasesResource[$db]);

        if ($orm == 'pdo' || $orm == '') $adapter = new Adapter($dbConfig);
        else if ($orm == 'doctrine') $adapter = DoctrineBootstrap::bootstrap($dbConfig, $this->getResource('Package'), $this->getResource('AppStage'));

        $resource = 'Database_' . $db;

        if ($orm != '') $resource .= '_' . $orm;

        $this->addResource($resource, $adapter);
    }

    /**
     * resolveDependencies
     *
     * Returns an object with all its dependencies resolved
     *
     * @param string | object $object
     *
     * $object can be either:
     *
     *      - An object to be resolved (usually a controller)
     *              A controller has a model
     *              A model has a Database
     *      - A resource name
     *      - A Database_ID dependency
     *      - An entry in Dependencies resource
     *      - An object that has not dependencies but it is needed as a dependency for another objects
     *
     * @return mixed The object with all its dependencies resolved
     */
    public function resolveDependencies($object)
    {
        $dependencies = $this->getResource('Dependencies');

        //it can be an object
        if (is_object($object))
        {
            $className = get_class($object);

            if (array_key_exists($className, $dependencies))
            {
                foreach ($dependencies[$className] as $dependency => $value)
                {
                    $setMethod = 'set' . ucfirst($dependency);
                    $object->$setMethod($this->resolveDependencies($value));
                }
            }

            $this->addResource($className, $object);

            return 0;
        }
        //it can already be a resource
        else if ($this->resourceExists($object))
        {
            return $this->getResource($object);
        }
        //it can be a resource not already executed in bootstrap
        //this kind of resources are added to bootstrap by its own like any other bootstrap resource
        else if (method_exists($this, '_dependency' . current(explode("_", $object))))
        {
            $items = explode("_", $object);
            $db = $items[1];

            if (!isset($items[2])) $items[2] = '';
            $this->_dependencyDatabase($db, $items[2]);

            return $this->getResource($object);
        }
        //it is not an object but it has dependencies and we have to resolve them and then instantiate it
        else if (array_key_exists($object, $dependencies))
        {
            $resource = new $object();

            foreach ($dependencies[$object] as $dependency => $value)
            {
                $arg = $this->resolveDependencies($value);
                $setMethod = 'set' . ucfirst($dependency);
                $resource->$setMethod($arg);
            }

            $this->addResource($object, $resource);

            return $resource;
        }
        //it is not an object and it does not have dependencies but it must be instantiated
        //because it is needed as a dependency for another resource
        else
        {
            $this->addResource($object, new $object());

            return $this->getResource($object);
        }
    }

    /**
     * resolveController
     *
     * Resolves a controller injecting all its dependencies
     * (besides of the ones declared in 'application/configs/dependencies.php')
     *
     * Dependencies are:
     *
     *      - Package
     *      - Namespace
     *      - AppStage
     *      - EventSystem
     *      - Router
     *      - Layout
     *      - Router
     *      - ActionHelperBroker
     *      - Config
     *      - Params (Controller params from the Router)
     *      - View
     *      - Request
     *      - Session
     *      - Response
     *      - Cache
     *
     * @param string $package           The package of this controller
     * @param string $namespace         The namespace of this controller
     * @param object $controller        The controller to be resolved
     * @param string $controllerName    The controller name
     * @param string $action            The action name
     * @param string $viewPath          The view path
     * @param bool   $error             If it is the ErrorController or not
     */
    public function resolveController($package, $namespace, $controller, $controllerName, $action, $viewPath, $error = false)
    {
        $controller->setAppStage($this->getResource('AppStage'));
        $controller->setEventSystem($this->getResource('EventSystem'));
        $controller->setRouter($this->getResource('Router'));
        $controller->setCache($this->getResource('Cache'));
        $controller->setPackage($package);
        $controller->setNamespace($namespace);

        $layout = ($error) ? $this->getResource('ErrorLayout') : $this->getResource('Layout');
        $controller->setLayout($layout);

        $controller->setActionHelperBroker($this->getResource('ActionHelperBroker'));
        $controller->setConfig($this->getResource('Config'));

        $extendedViewPath = 'application/packages/' . str_replace('\\', DIRECTORY_SEPARATOR, $package) . '/views/scripts/';
        if ($viewPath != '') $extendedViewPath .= $viewPath . '/'; // Controllers in folders for better organization
        $extendedViewPath .= lcfirst($controllerName);

        $view = new Phtml($extendedViewPath . DIRECTORY_SEPARATOR . $action . '.phtml');
        $controller->setView($view);

        if (!$error)
        {
            $request = $this->getResource('Request');
            $request->setController(lcfirst($controllerName));
            $request->setAction($action);
            $request->setParams($controller->getParams());
            $controller->setRequest($request);
        }

        $controller->setSession($this->getResource('Session'));
        $controller->setResponse($this->getResource('Response'));

        $this->resolveDependencies($controller);
    }
}
