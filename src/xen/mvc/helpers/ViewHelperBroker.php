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

namespace xen\mvc\helpers;

use xen\application\Router;

/**
 * Class ViewHelperBroker
 *
 * The ViewHelperBroker Factory
 *
 * @package    xenframework
 * @subpackage xen\mvc\helpers
 * @author     Ismael Trascastro <itrascastro@xenframework.com>
 * @copyright  Copyright (c) xenFramework. (http://xenframework.com)
 * @license    MIT License - http://en.wikipedia.org/wiki/MIT_License
 * @link       https://github.com/xenframework/xen
 * @since      Class available since Release 1.0.0
 */
class ViewHelperBroker extends HelperBroker
{
    /**
     * @var Router The router to be passed to
     */
    private $_router;

    /**
     * __construct
     *
     * set the namespaces and the paths for the View Helpers
     */
    public function __construct()
    {
        $this->_libNamespace = 'xen\\mvc\\helpers\\viewHelpers\\';
        $this->_appNamespace = 'views\\helpers\\';
        $this->_libPath      = str_replace('/', DIRECTORY_SEPARATOR, 'vendor/xen/mvc/helpers/viewHelpers/');
        $this->_appPath      = str_replace('/', DIRECTORY_SEPARATOR, 'application/views/helpers/');
    }

    /**
     * getRouter
     *
     * @return \xen\application\Router
     */
    public function getRouter()
    {
        return $this->_router;
    }

    /**
     * setRouter
     *
     * @param \xen\application\Router $router
     */
    public function setRouter($router)
    {
        $this->_router = $router;
    }

    /**
     * getHelper
     *
     * Creates the helper and set the router
     *
     * @param string $helper
     * @param array  $params
     *
     * @return mixed The view helper
     */
    public function getHelper($helper, $params=array())
    {
        parent::getHelper($helper, $params);

        $viewHelper = new $this->_helperClassName();

        $viewHelper->setParams($params);
        $viewHelper->setRouter($this->_router);

        return $viewHelper;
    }
}
