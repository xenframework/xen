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

require str_replace('/', DIRECTORY_SEPARATOR, 'vendor/xen/application/bootstrap/Autoloader.php');

class Application 
{
    const DEVELOPMENT   = 'development';
    const TEST          = 'test';
    const PRODUCTION    = 'production';

    private $_request;
    private $_autoLoader;
    private $_bootstrap;
    private $_frontController;
    private $_appEnv;

    public function __construct($_appEnv)
    {
        $this->_appEnv = $_appEnv;
        $this->_autoLoader();
        $this->_request = Request::createFromGlobals();
    }

    private function _autoLoader()
    {
        $this->_autoLoader = new Autoloader(array('application', 'vendor'));
        $this->_autoLoader->register();
    }

    public function bootstrap()
    {
        $this->_bootstrap = new Bootstrap($this->_appEnv);
        $this->_bootstrap->addResource('AutoLoader', $this->_autoLoader);
        $this->_bootstrap->addResource('Request', $this->_request);
        $this->_bootstrap->bootstrap();
    }

    public function run()
    {
        $this->_frontController = new FrontController($this->_bootstrap, $this->_request);
        $this->_frontController->run();
    }
}
