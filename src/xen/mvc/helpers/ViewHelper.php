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

use xen\kernel\Router;

/**
 * Class ViewHelper
 *
 * ViewHelper Abstraction
 *
 * @package    xenframework
 * @subpackage xen\mvc\helpers
 * @author     Ismael Trascastro <itrascastro@xenframework.com>
 * @copyright  Copyright (c) xenFramework. (http://xenframework.com)
 * @license    MIT License - http://en.wikipedia.org/wiki/MIT_License
 * @link       https://github.com/xenframework/xen
 * @since      Class available since Release 1.0.0
 */
abstract class ViewHelper
{
    /**
     * @var string The ViewHelper html generated
     */
    protected $_html;

    /**
     * @var array The params from the view
     */
    protected $_params;

    /**
     * @var Router The router to enable url generation in view helpers
     */
    protected $_router;

    /**
     * __construct
     */
    public function __construct()
    {
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

    /**
     * html
     *
     * This method has to be used to set the $_html variable
     *
     * @return mixed
     */
    abstract protected function _html();

    /**
     * getRouter
     *
     * @return \xen\kernel\Router
     */
    public function getRouter()
    {
        return $this->_router;
    }

    /**
     * setRouter
     *
     * @param \xen\kernel\Router $router
     */
    public function setRouter($router)
    {
        $this->_router = $router;
    }

    /**
     * show
     *
     * Method used for retrieve the ViewHelper html
     *
     * @return string
     */
    public function show()
    {
        $this->_html();

        return $this->_html;
    }
}
