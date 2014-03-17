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
 * @package xen
 * @author Ismael Trascastro itrascastro@xenframework.com
 *
 */
class Autoloader
{
    private $_includePaths;

    /**
     * @param string|array $_includePath one or more include paths to use
     */
    public function __construct($_includePath)
    {
        $this->_includePaths = (array) $_includePath;
    }

    /**
     * Sets the include paths to be used in this autoloader
     *
     * @param string|array $_includePath one or more include paths to use
     */
    public function setIncludePath($_includePath)
    {
        $this->_includePaths = (array) $_includePath;
    }

    /**
     * Creates a new entry in SPL stack
     *
     * @return bool
     */
    public function register()
    {
        return spl_autoload_register(array($this,'_autoload'));
    }

    /**
     * Removes an entry from SPL stack
     *
     * @return bool
     */
    public function unregister()
    {
        return spl_autoload_unregister(array($this,'_autoload'));
    }

    /**
     * It is mandatory to put the require sentence into an if statement
     * Otherwise php autoload will use this function even if the file does not exist
     *
     * We use require instead of require_once because of better performance
     * require_once looks into a log every time we try to require a file
     * info: http://gazehawk.com/blog/php-require-performance/
     *
     * @param $className to be autoloaded
     *
     * @return bool false if autoload attempt fails true otherwise
     */
    private function _autoload($className)
    {
        $file = str_replace('\\', DIRECTORY_SEPARATOR, $className) . '.php';

        foreach($this->_includePaths as $includePath) {
            $attemptfile = $includePath . DIRECTORY_SEPARATOR . $file;
            if ($this->_require($attemptfile)) {
                return true;
            }
        }

        return false;
    }

    private function _require($file)
    {
        if (file_exists($file)) {
            require $file;
            return true;
        }
        return false;
    }
}
