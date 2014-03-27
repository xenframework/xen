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
use xen\application\bootstrap\Autoloader;
use xen\http\Request;

require str_replace('/', DIRECTORY_SEPARATOR, 'vendor/xen/application/Error.php');
require str_replace('/', DIRECTORY_SEPARATOR, 'vendor/xen/application/bootstrap/Autoloader.php');

/**
 * Class Application
 *
 * Description
 *
 * @package    xenframework
 * @subpackage xen\application
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
     * @var Bootstrap
     */
    private $_bootstrap;

    /**
     * @var FrontController
     */
    private $_frontController;

    /**
     * @var string
     */
    private $_appEnv;

    /**
     * @var Error;
     */
    private $_error;

    /**
     * __construct
     *
     * Creates the Uncaught Exception handler to manage all the exceptions in the core
     * Creates the Error
     * Defines the application state
     * Call the autoloader
     * Creates the Request object from Globals
     *
     * @param string $_appEnv {DEVELOPMENT, TEST, PRODUCTION} Defines the application state
     */
    public function __construct($_appEnv)
    {
        $this->_error = new Error();
        $this->_appEnv = $_appEnv;
        $this->_autoLoader();
        $this->_request = Request::createFromGlobals();
    }

    /**
     * _autoLoader
     *
     * Enables auto load for 'application' and 'vendor' directories
     * It is done in the same autoloader because it is more efficient (instead of one autoloader per directory)
     */
    private function _autoLoader()
    {
        $this->_autoLoader = new Autoloader(array('application', 'vendor'));
        $this->_autoLoader->register();
    }

    /**
     * bootstrap
     *
     * The Bootstrap object is created with 4 initial resources (appEnv, Autoloader, Request, Error) and then we
     * bootstrap the application
     */
    public function bootstrap()
    {
        $this->_bootstrap = new Bootstrap();
        $this->_bootstrap->addResource('AppEnv', $this->_appEnv);
        $this->_bootstrap->addResource('AutoLoader', $this->_autoLoader);
        $this->_bootstrap->addResource('Request', $this->_request);
        $this->_bootstrap->addResource('Error', $this->_error);
        $this->_bootstrap->bootstrap();
    }

    /**
     * run
     *
     * FrontController is created with the Bootstrap as a Service Locator
     * and then the application execution starts with FrontController run method
     */
    public function run()
    {
        $this->_frontController = new FrontController($this->_bootstrap);
        $this->_frontController->run();
    }
}
