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

/**
 * Class Autoloader
 *
 * Allow auto load of classes that are inside of registered directories
 * using spl_autoload_register
 *
 * An auto loader for each directory can be created but it is more efficient to have one auto loader with many
 * directories registered
 *
 * @package    xenframework
 * @subpackage xen\application\bootstrap
 * @author     Ismael Trascastro <itrascastro@xenframework.com>
 * @copyright  Copyright (c) xenFramework. (http://xenframework.com)
 * @license    MIT License - http://en.wikipedia.org/wiki/MIT_License
 * @link       https://github.com/xenframework/xen
 * @since      Class available since Release 1.0.0
 */
class Autoloader
{
    /**
     * Set of directories to be used in auto load
     *
     * @var array
     */
    private $_includePaths;

    /**
     * __construct
     *
     * Sets the $_includePaths
     *
     * @param string|array $_includePath one or more include paths to use
     */
    public function __construct($_includePath)
    {
        $this->_includePaths = (array) $_includePath;
    }

    /**
     * setIncludePath
     *
     * Sets the include paths to be used in this auto loader
     *
     * @param string|array $_includePath one or more include paths to use
     */
    public function setIncludePath($_includePath)
    {
        $this->_includePaths = (array) $_includePath;
    }

    /**
     * addIncludePath
     *
     * @param string $path
     */
    public function addIncludePath($path)
    {
        $this->_includePaths[] = $path;
    }

    /**
     * register
     *
     * Creates a new entry in SPL stack
     *
     * @return bool
     */
    public function register()
    {
        return spl_autoload_register(array($this,'_autoload'));
    }

    /**
     * unregister
     *
     * Removes an entry from SPL stack
     *
     * @return bool
     */
    public function unregister()
    {
        return spl_autoload_unregister(array($this,'_autoload'));
    }

    /**
     * _autoload
     *
     * This function will be used by spl_autoload_register when a new $className instance is created
     *
     * It is mandatory to test if the file exists because require does not return any value.
     * If the test is avoided and there are more than one directories in the includePath, _autoload will always choose
     * the first one, even if the file is in another directory
     *
     * Directory structure has to be the same as the Namespace
     *
     * @param string $className The class to be instantiated
     *
     * @return bool true if the file exists in the path otherwise false
     */
    private function _autoload($className)
    {
        $file = str_replace('\\', DIRECTORY_SEPARATOR, $className) . '.php';

        foreach($this->_includePaths as $includePath) {

            $attemptFile = $includePath . DIRECTORY_SEPARATOR . $file;

            if ($this->_require($attemptFile)) {

                return true;
            }
        }

        return false;
    }

    /**
     * _require
     *
     * Try to require a file
     *
     * @param string $file
     *
     * @return bool true if file exists otherwise false
     */
    private function _require($file)
    {
        if (file_exists($file)) {

            require $file;
            return true;
        }

        return false;
    }
}
