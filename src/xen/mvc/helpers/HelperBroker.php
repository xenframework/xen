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

/**
 * Class HelperBroker
 *
 * @package xen\mvc\helpers
 * @author  Ismael Trascastro itrascastro@xenframework.com
 *
 * Used in View Files to create instances of view Helpers and then call them (We do not have to create
 * instances in View Files. That is the reason to be)
 *
 */
class HelperBroker
{
    const ACTION_HELPER = 0;
    const VIEW_HELPER = 1;

    private $_type;
    private $_libNamespace;
    private $_appNamespace;
    private $_libPath;
    private $_appPath;

    public function __construct($_type)
    {
        if ($_type == self::ACTION_HELPER) {
            $this->_type = $_type;
            $this->_libNamespace = 'xen\\mvc\\helpers\\actionHelpers\\';
            $this->_appNamespace = 'controllers\\helpers\\';
            $this->_libPath      = str_replace('/', DIRECTORY_SEPARATOR, 'vendor/xen/mvc/helpers/actionHelpers/');
            $this->_appPath      = str_replace('/', DIRECTORY_SEPARATOR, 'application/controllers/helpers/');
        } else if ($_type == self::VIEW_HELPER) {
            $this->_type = $_type;
            $this->_libNamespace = 'xen\\mvc\\helpers\\viewHelpers\\';
            $this->_appNamespace = 'views\\helpers\\';
            $this->_libPath      = str_replace('/', DIRECTORY_SEPARATOR, 'vendor/xen/mvc/helpers/viewHelpers/');
            $this->_appPath      = str_replace('/', DIRECTORY_SEPARATOR, 'application/views/helpers/');
        } else {
            $this->_type = null;
        }
    }

    public function getHelper($helper, $params=array())
    {
        if ($this->isLibraryHelper($helper)) {
            $className = $this->_libNamespace . $helper;
        } else if ($this->isApplicationHelper($helper)) {
            $className = $this->_appNamespace . $helper;
        } else $className = null;

        if ($className != null) return new $className($params);

        return null;
    }

    public function helperExists($helper)
    {
        return $this->isLibraryHelper($helper) || $this->isApplicationHelper($helper);
    }

    public function isLibraryHelper($helper)
    {
        return file_exists($this->_libPath . $helper . '.php');
    }

    public function isApplicationHelper($helper)
    {
        return file_exists($this->_appPath . $helper . '.php');
    }
}
