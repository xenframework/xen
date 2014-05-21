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
use xen\mvc\helpers\exception\HelperNotFoundException;

/**
 * Class HelperBroker
 *
 * A Helpers factory
 *
 * @package    xenframework
 * @subpackage xen\mvc\helpers
 * @author     Ismael Trascastro <itrascastro@xenframework.com>
 * @copyright  Copyright (c) xenFramework. (http://xenframework.com)
 * @license    MIT License - http://en.wikipedia.org/wiki/MIT_License
 * @link       https://github.com/xenframework/xen
 * @since      Class available since Release 1.0.0
 */
class HelperBroker
{
    /**
     * @var string The Helper Namespace in the xen library
     */
    protected $_libNamespace;

    /**
     * @var string The Helper Namespace in the application (IoC)
     */
    protected $_appNamespace;

    /**
     * @var string The Helper path in the xen library
     */
    protected $_libPath;

    /**
     * @var string The Helper path in the application (IoC)
     */
    protected $_appPath;

    /**
     * @var string The helper class name
     */
    protected $_helperClassName;

    /**
     * @var array The helper params
     */
    protected $_helperParams;

    /**
     * @var string
     */
    protected $_package;

    /**
     * @param mixed $package
     */
    public function setPackage($package)
    {
        $this->_package = $package;
    }

    /**
     * @return mixed
     */
    public function getPackage()
    {
        return $this->_package;
    }

    /**
     * getHelper
     *
     * The Factory
     * Looks for a helper in the xen library or in the application path
     *
     * @param string    $helper
     * @param array     $params
     *
     * @throws exception\HelperNotFoundException
     */
    protected function getHelper($helper, $params=array())
    {
        if ($this->isLibraryHelper($helper)) $this->_helperClassName = $this->_libNamespace . $helper;
        else if ($this->isApplicationHelper($helper)) $this->_helperClassName = $this->_appNamespace . $helper;
        else throw new HelperNotFoundException('The helper ' . $helper . ' does not exist');

        $this->_helperParams = $params;
    }

    /**
     * helperExists
     *
     * If a helper exists in xen library or in application path
     *
     * @param string $helper
     *
     * @return bool
     */
    public function helperExists($helper)
    {
        return $this->isLibraryHelper($helper) || $this->isApplicationHelper($helper);
    }

    /**
     * isLibraryHelper
     *
     * If a helper exists in xen library
     *
     * @param string $helper
     *
     * @return bool
     */
    public function isLibraryHelper($helper)
    {
        return file_exists($this->_libPath . $helper . '.php');
    }

    /**
     * isApplicationHelper
     *
     * If a helper exists in application path
     *
     * @param string $helper
     *
     * @return bool
     */
    public function isApplicationHelper($helper)
    {
        return file_exists($this->_appPath . $helper . '.php');
    }
}
