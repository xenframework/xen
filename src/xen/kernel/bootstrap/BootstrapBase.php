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

namespace xen\kernel\bootstrap;

use xen\kernel\bootstrap\exception\BootstrapDependencyDatabaseNotFoundException;
use xen\kernel\bootstrap\exception\BootstrapResourceNotFoundException;
use xen\kernel\Cache;
use xen\kernel\Router;
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
 * bootstrap\Bootstrap inherits from this class and it is instantiated in xen\kernel\Application
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
 * @subpackage xen\kernel\bootstrap
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
    protected $_container;

    protected $_package;

    protected $_packagePath;

    /**
     * @var array
     */
    private $_initMethods;

    /**
     * @var array
     */
    private $_defaultMethods;

    /**
     * __construct
     *
     * Initializes the container to an empty array and calls to _separateMethods()
     */
    public function __construct($_package)
    {
        $this->_package = $_package;
        $this->_packagePath = str_replace('\\', DIRECTORY_SEPARATOR, $_package);
        $this->_separateMethods();
    }

    /**
     * @param array $container
     */
    public function setContainer($container)
    {
        $this->_container = $container;
    }

    /**
     * @return array
     */
    public function getContainer()
    {
        return $this->_container;
    }

    /**
     * _separateMethods
     *
     *      - default:  Resources defined in BootstrapBase
     *      - init:     Resources defined in application\Bootstrap
     */
    private function _separateMethods()
    {
        $allMethods = get_class_methods($this);

        $this->_defaultMethods  = array();
        $this->_initMethods     = array();

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

    /**
     * minimalBootstrap
     *
     * This method only creates the resources needed when the response will be from the cache
     *
     *      - Session
     *      - Role
     *      - Cache
     *      - Router
     *
     */
    public function minimalBootstrap()
    {
        if (method_exists($this, '_initRole'))
        {
            $role = $this->_initRole();
            $this->_container->addResource('Role', $role);
        }
    }

    /**
     * _bootstrapDefaults
     *
     * Creates _default Resources
     *
     */
    private function _bootstrapDefaults()
    {
        forEach($this->_defaultMethods as $method)
        {
            $resourceName = ucfirst(substr($method, 8));
            $this->_container->addResource($resourceName, $this->$method());
        }
    }

    /**
     * _bootstrapInit
     *
     * Creates _init Resources
     *
     */
    private function _bootstrapInit()
    {
        forEach($this->_initMethods as $method)
        {
            $resourceName = ucfirst(substr($method, 5));
            $this->_container->addResource($resourceName, $this->$method());
        }
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
        return new Ini('application/configs/application.ini', $this->_container->getResource('AppStage'));
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
        $config = require 'application/packages/' . $this->_packagePath . '/configs/config.php';
        return new Config($config);
    }

    protected function _defaultPackage()
    {
        return $this->_package;
    }

    protected function _defaultPackagePath()
    {
        return $this->_packagePath;
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
        $viewHelperBroker = new ViewHelperBroker($this->_container->getResource('Package'));
        $viewHelperBroker->setRouter($this->_container->getResource('Router'));

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
        return new ActionHelperBroker($this->_container->getResource('Package'));
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
        $applicationConfig = $this->_container->getResource('ApplicationConfig');
        $config = $this->_container->getResource('Config');

        if (isset($config->mvc->layoutPath)) return $config->mvc->layoutPath;

        if (isset($applicationConfig->layoutPath)) return $applicationConfig->layoutPath;

        return ('application/packages/main/layouts/default');
    }

    protected function _defaultErrorLayoutPath()
    {
        $applicationConfig = $this->_container->getResource('ApplicationConfig');
        $config = $this->_container->getResource('Config');

        if (isset($config->errorLayoutPath)) return $config->errorLayoutPath;

        if (isset($applicationConfig->errorLayoutPath)) return $applicationConfig->errorLayoutPath;

        return ('application/packages/main/layouts/error');
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
        $layout =  new Phtml($this->_container->getResource('LayoutPath') . DIRECTORY_SEPARATOR . 'layout.phtml');

        $layout->setRouter($this->_container->getResource('Router'));
        $layout->setViewHelperBroker($this->_container->getResource('ViewHelperBroker'));
        $layout->setCache($this->_container->getResource('Cache'));

        return $layout;
    }

    protected function _defaultErrorLayout()
    {
        $layout = new Phtml($this->_container->getResource('ErrorLayoutPath') . DIRECTORY_SEPARATOR . 'exception.phtml');

        $layout->setRouter($this->_container->getResource('Router'));
        $layout->setViewHelperBroker($this->_container->getResource('ViewHelperBroker'));
        $layout->setCache($this->_container->getResource('Cache'));

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
        $eventSystem = new EventSystem();
        $eventSystem->setPackage($this->_container->getResource('Package'));
        return $eventSystem;
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
        $handlers = require str_replace('/', DIRECTORY_SEPARATOR, 'application/packages/' . $this->_packagePath . '/configs/handlers.php');

        $eventSystem = $this->_container->getResource('EventSystem');

        foreach ($handlers as $handler => $events)
        {
            $handlerClassName = $this->_package . '\\eventHandlers\\' . $handler;
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
        $dependencies = array();

        $packages = $this->_container->getResource('Packages');

        foreach ($packages as $package)
        {
            $packagePath    = 'application/packages/' . str_replace('\\', DIRECTORY_SEPARATOR, $package);
            $packageDependencies         = require $packagePath . DIRECTORY_SEPARATOR . 'configs/dependencies.php';

            foreach ($packageDependencies as $dependency => $value)
            {
                $dependencies[$dependency] = $value;
            }
        }

        return $dependencies;
    }
}
