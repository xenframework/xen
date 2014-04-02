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

namespace xen\http;

/**
 * Class Session
 *
 * The Session
 *
 * @package    xenframework
 * @subpackage xen\http
 * @author     Ismael Trascastro <itrascastro@xenframework.com>
 * @copyright  Copyright (c) xenFramework. (http://xenframework.com)
 * @license    MIT License - http://en.wikipedia.org/wiki/MIT_License
 * @link       https://github.com/xenframework/xen
 * @since      Class available since Release 1.0.0
 */
class Session 
{
    /**
     * start
     *
     * If session is not already started it will be started
     *
     * Uses session_status function when php version is almost 5.4.0
     */
    public function start()
    {
        if (version_compare(phpversion(), "5.4.0", ">=") && session_status() !== PHP_SESSION_ACTIVE ||
            session_id() == ''
        )
            session_start();

    }

    /**
     * set
     *
     * @param string $name
     * @param mixed $value
     */
    public function set($name, $value)
    {
        $_SESSION[$name] = $value;
    }

    /**
     * get
     *
     * @param $name
     *
     * @return mixed|null
     */
    public function get($name)
    {
        return (isset($_SESSION[$name])) ? $_SESSION[$name] : null;
    }

    /**
     * delete
     *
     * @param string $name
     */
    public function delete($name)
    {
        unset($_SESSION[$name]);
    }

    /**
     * destroy
     */
    public function destroy()
    {
        session_destroy();
    }
} 