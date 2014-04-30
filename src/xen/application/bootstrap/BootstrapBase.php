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

namespace xen\application\bootstrap;

use xen\application\bootstrap\exception\BootstrapDependencyDatabaseNotFoundException;
use xen\application\bootstrap\exception\BootstrapResourceNotFoundException;
use xen\application\Cache;
use xen\application\Router;
use xen\config\Config;
use xen\config\Ini;
use xen\db\Adapter;
use xen\eventSystem\EventSystem;
use xen\http\Session;
use xen\mvc\helpers\ActionHelperBroker;
use xen\mvc\helpers\ViewHelperBroker;
use xen\mvc\view\Phtml;

/**
 * Class BootstrapBase
 *
 * No instances of BootstrapBase are created.
 * bootstrap\Bootstrap inherits from this class and it is instantiated in xen\application\Application
 *
 * Bootstrap does three things:
 *
 *      1. Bootstrap the application
 *      2. DIC (Dependency Injection Container)
 *      3. Resolve controllers dependencies
 *
 * BootstrapBase creates the default resources/dependencies. It is used a base class to extend from by the
 * bootstrap\Bootstrap where an IoC takes place by the application. There the specific
 * resources/dependencies can be created.
 *
 * Bootstrap is used as a SL (Service Locator) in the FrontController. This is the only place where it happens.
 * Controllers can not access to the Bootstrap. All the dependencies are injected to them from the Bootstrap.
 *
 * Dependencies IoC
 *
 * Application can define his own dependencies in 'application/configs/dependencies.php' which is used to resolve the
 * controller dependencies
 *
 * The resources/dependencies created in Bootstrap are:
 *
 *      - Role                  => Default role for ACL. It will be set to 'guest'
 *      - Config                => 'application/configs/config.ini'
 *      - ApplicationConfig     => 'application/configs/application.ini'
 *      - Router                => The router
 *      - ViewHelperBroker      => Factory for view helpers
 *      - ActionHelperBroker    => Factory for action helpers
 *      - LayoutPath            => Path to the default layout
 *      - Layout                => The default layout
 *      - EventSystem           => System for manage events
 *      - Dependencies          => 'application/configs/dependencies.php'
 *      - Database_X            => These resources are not created by default
 *      - Session               => Starts the session
 *
 * Other resources stored in the Bootstrap are:
 *
 *      - Request               => The Request
 *      - Response              => The Response
 *      - AppStage              => Application development stage
 *      - Autoloader            => The Auto Loader
 *      - Error                 => Manages core exceptions
 *
 * Other bootstrap actions like:
 *
 *      - Read handlers from 'application/configs/handlers.php' and add them to EventSystem
 *
 * @package    xenframework
 * @subpackage xen\application\bootstrap
 * @author     Ismael Trascastro <itrascastro@xenframework.com>
 * @copyright  Copyright (c) xenFramework. (http://xenframework.com)
 * @license    MIT License - http://en.wikipedia.org/wiki/MIT_License
 * @link       https://github.com/xenframework/xen
 * @since      Class available since Release 1.0.0
 */
class BootstrapBase
{
    /**
     * Container for dependencies
     *
     * @var array
     */
    private $_resources;

    /**
     * @var array
     */
    private $_initMethods;

    private $_defaultMethods;

    /**
     * __construct
     *
     * Initializes the container to an empty array
     */
    public function __construct()
    {
        $this->_resources = array();
        $this->_separateMethods();
    }

    private function _separateMethods()
    {
        $allMethods = get_class_methods($this);

        $this->_defaultMethods  = array();
        $this->_initMethods     = array();
        $this->_minimalMethods  = array();

        foreach ($allMethods as $method)
        {
            if (strlen($method) > 8 && substr($method, 0, 8) == '_default') $this->_defaultMethods[] = $method;
            else if (strlen($method) > 5 && substr($method, 0, 5) == '_init' && $method != '_initRole')
                $this->_initMethods[] = $method;
        }
    }

    /**
     * bootstrap
     *
     * Calls all Bootstrap methods
     *
     * First the ones located here in BootstrapBase (_default) and then the ones located at
     * application\Bootstrap\Bootstrap (_init)
     *
     * Every call to an _default method or _init method (located at application\Bootstrap\Bootstrap) returns a
     * resource/dependency which is stored in the container $this->_resources
     *
     */
    public function bootstrap()
    {
        $this->_bootstrapDefaults();
        $this->_bootstrapInit();
    }

    public function minimalBootstrap()
    {
        $this->addResource('Session', $this->_minimalSession());

        if (method_exists($this, '_initRole')) $role = $this->_initRole();
        else $role = $this->_minimalRole();

        $this->addResource('Role', $role);

        $this->addResource('Cache', $this->_minimalCache());
        $this->addResource('Router', $this->_minimalRouter());
    }

    /**
     * _bootstrapDefaults
     *
     * Calls all _default methods and returns all _init methods
     *
     * @param array $methods All the methods in bootstrap\Bootstrap inherited from here included
     *
     * @return array The _init methods from bootstrap\Bootstrap
     */
    private function _bootstrapDefaults()
    {
        forEach($this->_defaultMethods as $method)
        {
            $resourceName = ucfirst(substr($method, 8));
            $this->addResource($resourceName, $this->$method());
        }
    }

    private function _bootstrapInit()
    {
        forEach($this->_initMethods as $method)
        {
            $resourceName = ucfirst(substr($method, 5));
            $this->addResource($resourceName, $this->$method());
        }
    }

    /**
     * addResources
     *
     * Stores an array of resources in the container
     *
     * @param array $resources Associative array resource => value
     */
    public function addResources(array $resources)
    {
        foreach ($resources as $resource => $value) {

            $this->_resources[$resource] = $value;
        }
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
     * @throws exception\BootstrapResourceNotFoundException
     * @return mixed The resource
     */
    public function getResource($resource)
    {
        if ($this->resourceExists($resource)) return $this->_resources[$resource];

        throw new BootstrapResourceNotFoundException('Resource ' . $resource . ' does not exist in Bootstrap');
    }

    public function resourceExists($resource)
    {
        return array_key_exists($resource, $this->_resources);
    }

    /**
     * _defaultRouter
     *
     * Router resource
     *
     * @return Router
     */
    protected function _minimalRouter()
    {
        return new Router();
    }

    protected function _minimalCache()
    {
        return new Cache('application/cache');
    }

    /**
     * _initRole
     *
     * The Role resource
     *
     * Set the default role for ACL
     *
     * @return string
     */
    protected function _minimalRole()
    {
        return 'guest';
    }

    /**
     * _defaultSession
     *
     * The Session resource
     *
     * @return Session
     */
    protected function _minimalSession()
    {
        $session = new Session();

        $session->start();

        return $session;
    }

    /**
     * _defaultApplicationConfig
     *
     * Load the application.ini and stores it as a resource
     *
     * @return Ini The ApplicationConfig resource
     */
    protected function _defaultApplicationConfig()
    {
        return new Ini('application/configs/application.ini', $this->getResource('AppStage'));
    }

    /**
     * _defaultConfig
     *
     * Load the config.ini and stores it as a resource
     *
     * @return Ini The Config resource
     */
    protected function _defaultConfig()
    {
        return new Ini('application/configs/config.ini', $this->getResource('AppStage'));
    }

    /**
     * _defaultViewHelperBroker
     *
     * ViewHelperBroker resource
     *
     * It is a factory for View Helpers
     * Injects the Router
     *
     * @return ViewHelperBroker
     */
    protected function _defaultViewHelperBroker()
    {
        $viewHelperBroker = new ViewHelperBroker();
        $viewHelperBroker->setRouter($this->getResource('Router'));

        return $viewHelperBroker;
    }

    /**
     * _defaultActionHelperBroker
     *
     * ActionHelperBroker resource
     *
     * It is a factory for Action Helpers
     *
     * @return ActionHelperBroker
     */
    protected function _defaultActionHelperBroker()
    {
        return new ActionHelperBroker();
    }

    /**
     * _defaultLayoutPath
     *
     * The default layout path defined in configs/application.ini
     * If not defined then it will be set to 'application/layouts/default'
     *
     * @return string layout path
     */
    protected function _defaultLayoutPath()
    {
        $applicationConfig = $this->getResource('ApplicationConfig');

        return (isset($applicationConfig->defaultLayoutPath)) ?
            str_replace('/', DIRECTORY_SEPARATOR, $applicationConfig->defaultLayoutPath) :
            str_replace('/', DIRECTORY_SEPARATOR, 'application/layouts/default');
    }

    /**
     * _defaultLayout
     *
     * Creates a default layout
     *
     * ViewHelperBroker is set here, it will be propagated to child partials in render() method
     *
     * @return Phtml The layout
     */
    protected function _defaultLayout()
    {
        $layout =  new Phtml($this->getResource('LayoutPath') . DIRECTORY_SEPARATOR . 'layout.phtml');

        $layout->setRouter($this->getResource('Router'));
        $layout->setViewHelperBroker($this->getResource('ViewHelperBroker'));
        $layout->setCache($this->getResource('Cache'));

        return $layout;
    }

    /**
     * _defaultEventSystem
     *
     * EventSystem resource
     *
     * @return EventSystem
     */
    protected function _defaultEventSystem()
    {
        return new EventSystem();
    }

    /**
     * _defaultHandlers
     *
     * Factory for handlers from 'application/configs/handlers.php'
     *
     * There are two kinds of handlers:
     *
     *      1. The ones who have the same name as the event they handle
     *         (no need to define them in 'application/configs/handlers.php')
     *
     *      2. The ones who have a different name or the ones who handle more than one event
     *         (they are defined in 'application/configs/handlers.php')
     *
     * This bootstrap method is for the second kind
     *
     * It does not create a resource but it create all handlers located at 'application/configs/handlers.php' and add
     * them to the eventSystem
     */
    protected function _defaultHandlers()
    {
        $handlers = require str_replace('/', DIRECTORY_SEPARATOR, 'application/configs/handlers.php');

        $eventSystem = $this->getResource('EventSystem');

        foreach ($handlers as $handler => $events) {

            $handlerClassName = 'eventHandlers\\' . $handler;
            $handlerInstance = new $handlerClassName();
            $handlerInstance->addHandles($events);
            $eventSystem->addHandler($handlerInstance);
        }
    }

    /**
     * _defaultDependencies
     *
     * IoC loading dependencies from 'application/configs/dependencies.php'
     *
     * @return array The dependencies
     */
    protected function _defaultDependencies()
    {
        return require str_replace('/', DIRECTORY_SEPARATOR, 'application/configs/dependencies.php');
    }

    /**
     * _dependencyDatabase
     *
     * Load the database config 'application/configs/databases.php'
     *
     * Can exist more than one database:
     *
     *      The first time 'application/configs/databases.php' is loaded into Databases resource
     *
     *      Each Database resource is named as follows: Database_ID
     *
     *      So when this method is called with $db ID, it creates the Database_$db resource (an Adapter instance)
     *      and stores it in the container
     *
     *
     * @param string $db The ID of the database
     *
     * @throws exception\BootstrapDependencyDatabaseNotFoundException
     */
    protected function _dependencyDatabase($db)
    {
        if (!$this->resourceExists('Databases')) {

            $databases = require str_replace('/', DIRECTORY_SEPARATOR, 'application/configs/databases.php');
            $this->addResource('Databases', $databases);
        }

        if (!array_key_exists($db, $this->getResource('Databases')))
            throw new BootstrapDependencyDatabaseNotFoundException('Dependency database ' . $db .
                                                          ' not found in "application/configs/databases.php"');

        $databasesResource = $this->getResource('Databases');
        $dbConfig = new Config($databasesResource[$db]);

        $this->addResource('Database_' . $db, new Adapter($dbConfig));
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
        if (is_object($object)) {

            $className = get_class($object);

            if (array_key_exists($className, $dependencies)) {

                foreach ($dependencies[$className] as $dependency => $value) {

                    $setMethod = 'set' . ucfirst($dependency);
                    $object->$setMethod($this->resolveDependencies($value));
                }
            }

            $this->addResource($className, $object);

            return 0;

        //it can already be a resource
        } else if ($this->resourceExists($object)) {

            return $this->getResource($object);

        //it can be a resource not already executed in bootstrap
        //this kind of resources are added to bootstrap by its own like any other bootstrap resource
        } else if (method_exists($this, '_dependency' . current(explode("_", $object)))) {

            $db = substr($object, 9);
            $this->_dependencyDatabase($db);
            return $this->getResource($object);

        //it is not an object but it has dependencies and we have to resolve them and then instantiate it
        } else if (array_key_exists($object, $dependencies)) {

            $resource = new $object();

            foreach ($dependencies[$object] as $dependency => $value) {

                $arg = $this->resolveDependencies($value);
                $setMethod = 'set' . ucfirst($dependency);
                $resource->$setMethod($arg);
            }

            $this->addResource($object, $resource);
            return $resource;

        //it is not an object and it does not have dependencies but it must be instantiated
        //because it is needed as a dependency for another resource
        } else {

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
     * @param object    $controller         The controller to be resolved
     * @param string    $controllerName     The controller name
     * @param string    $action             The action name
     * @param bool      $error              If it is the ErrorController or not
     */
    public function resolveController($controller, $controllerName, $action, $viewPath, $error = false)
    {
        $controller->setAppStage($this->getResource('AppStage'));
        $controller->setEventSystem($this->getResource('EventSystem'));
        $controller->setRouter($this->getResource('Router'));
        $controller->setCache($this->getResource('Cache'));

        $layout = ($error) ? clone $this->getResource('Layout') : $this->getResource('Layout');
        $controller->setLayout($layout);

        $controller->setActionHelperBroker($this->getResource('ActionHelperBroker'));
        $controller->setConfig($this->getResource('Config'));

        if ($viewPath != '')
        {
            $viewPath = str_replace('/', DIRECTORY_SEPARATOR,
                'application/views/scripts/' . $viewPath . '/' . lcfirst($controllerName));
        }
        else
        {
            $viewPath = str_replace('/', DIRECTORY_SEPARATOR,
                'application/views/scripts/' . lcfirst($controllerName));
        }



        $view = new Phtml($viewPath . DIRECTORY_SEPARATOR . $action . '.phtml');
        $controller->setView($view);

        if (!$error) {

            $request = $this->getResource('Request');
            $request->setController(lcfirst($controllerName));
            $request->setAction($action);
            $request->setParams($this->getResource('Router')->getParams());
            $controller->setRequest($request);
        }

        $controller->setSession($this->getResource('Session'));
        $controller->setResponse($this->getResource('Response'));

        $this->resolveDependencies($controller);
    }
}
