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

use xen\kernel\bootstrap\Autoloader;
use xen\kernel\error\Error;
use xen\http\Request;

require 'vendor/xen/kernel/error/Error.php';
require 'vendor/xen/kernel/bootstrap/Autoloader.php';

/**
 * Class Application
 *
 * Does an initial setUp before calling the Front Controller
 *
 *      - kernel exceptions management
 *      - AutoLoading
 *      - Initializes the container
 *
 * @package    xenframework
 * @subpackage xen\kernel
 * @author     Ismael Trascastro <itrascastro@xenframework.com>
 * @copyright  Copyright (c) xenFramework. (http://xenframework.com)
 * @license    MIT License - http://en.wikipedia.org/wiki/MIT_License
 * @link       https://github.com/xenframework/xen
 * @since      Class available since Release 1.0.0
 */
class Application 
{
    /**
     * @const DEVELOPMENT
     */
    const DEVELOPMENT   = 'development';

    /**
     * @const TEST
     */
    const TEST          = 'test';

    /**
     * @const PRODUCTION
     */
    const PRODUCTION    = 'production';

    /**
     * @var Request
     */
    private $_request;

    /**
     * @var Autoloader
     */
    private $_autoLoader;

    /**
     * @var Dic
     */
    private $_container;

    /**
     * @var FrontController
     */
    private $_frontController;

    /**
     * @var string
     */
    private $_appStage;

    /**
     * @var Error;
     */
    private $_error;

    /**
     * @var array
     */
    private $_packages;

    /**
     * __construct
     *
     * Creates the Uncaught Exception handler to manage all the exceptions in the core
     * Creates the Error
     * Defines the application state
     * Calls the autoloader
     * Creates the Request object from Globals
     * Loads the active packages
     *
     * @param string $_appStage {DEVELOPMENT, TEST, PRODUCTION} Defines the application state
     */
    public function __construct($_appStage)
    {
        $this->_error = new Error();
        $this->_appStage = $_appStage;
        $this->_autoLoader();
        $this->_request = Request::createFromGlobals();
        $this->_packages = require 'application/configs/packages.php';
        array_unshift($this->_packages, 'main');
    }

    /**
     * _autoLoader
     *
     * Enables auto load for 'application', 'application/packages' and 'vendor' directories
     * It is done in the same autoloader because it is more efficient (instead of one autoloader per directory)
     */
    private function _autoLoader()
    {
        $this->_autoLoader = new Autoloader(array('application', 'application/packages', 'vendor'));
        $this->_autoLoader->register();
    }

    /**
     * bootstrap
     *
     * The Bootstrap object is created with 5 initial resources (Packages, AppStage, Autoloader, Request, Error)
     * and then the container is initialized with the minimum resources for a cached response (Session, Role, Cache, Router)
     */
    private function _initDependencyInjectionContainer()
    {
        $this->_container = new Dic();

        $this->_container->addResource('Packages', $this->_packages);
        $this->_container->addResource('AppStage', $this->_appStage);
        $this->_container->addResource('AutoLoader', $this->_autoLoader);
        $this->_container->addResource('Request', $this->_request);
        $this->_container->addResource('Error', $this->_error);

        $this->_container->_initTheContainer();
    }

    /**
     * run
     *
     * FrontController is created with an initialized Container as a Service Locator
     * and then the application execution starts with FrontController run method
     */
    public function run()
    {
        $url = ($this->_request->getExists('url')) ? $this->_request->get('url') : '';
        $this->_request->setUrl($url);

        $this->_initDependencyInjectionContainer();

        $this->_frontController = new FrontController($this->_container);
        $this->_frontController->run();
    }
}
