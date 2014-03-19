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


class Session 
{
    public function __construct()
    {

    }

    public function start()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {

            session_start();
        }
    }

    public function set($name, $value)
    {
        $_SESSION[$name] = $value;
    }

    public function get($name)
    {
        return (isset($_SESSION[$name])) ? $_SESSION[$name] : null;
    }

    public function delete($name)
    {
        unset($_SESSION[$name]);
    }

    public function destroy()
    {
        session_destroy();
    }
} 