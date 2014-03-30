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

namespace xen\eventSystem;

/**
 * Class EventSystem
 *
 * Similar to Visual Basic .NET an event handler can be:
 *
 *      - a function with the same name as the event ('application/eventHandlers')
 *      - a function declared with handles clause ('application/configs/handlers.php')
 *
 *
 * @package    xenframework
 * @subpackage xen\eventSystem
 * @author     Ismael Trascastro <itrascastro@xenframework.com>
 * @copyright  Copyright (c) xenFramework. (http://xenframework.com)
 * @license    MIT License - http://en.wikipedia.org/wiki/MIT_License
 * @link       https://github.com/xenframework/xen
 * @since      Class available since Release 1.0.0
 */
class EventSystem 
{
    /**
     * @var array The handlers
     */
    private $_handlers;

    /**
     * __construct
     *
     * @param array $_handlers
     */
    public function __construct($_handlers = array())
    {
        $this->_handlers = $_handlers;
    }

    /**
     * raiseEvent
     *
     * There are two kinds of handlers:
     *
     *      1. The ones who have the same name as the event they handle
     *         (no need to define them in 'application/configs/handlers.php')
     *
     *      2. The ones who have a different name or the ones who handle more than one event
     *         (they are defined in 'application/configs/handlers.php')
     *
     * @param string    $event
     * @param array     $params
     */
    public function raiseEvent($event, $params = array())
    {
        foreach ($this->_handlers as $handler) {

            if ($handler->handles($event)) $handler->handle($params);
        }

        if (file_exists('application/eventHandlers/' . $event . '.php')) {

            $handlerClassName = 'eventHandlers\\' . $event;
            $handler = new $handlerClassName();
            $handler->handle($params);
        }
    }

    /**
     * addHandler
     *
     * New Handler is stored in the Event System
     *
     * @param $handler
     */
    public function addHandler($handler)
    {
        $this->_handlers[] = $handler;
    }
}
