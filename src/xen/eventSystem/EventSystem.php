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
 * @package xen\eventSystem
 * @author  Ismael Trascastro itrascastro@xenframework.com
 *
 *          Similar to VB an event handler can be a function with the same name as the event
 *          or can be handle by another function declared with handles clause
 */
class EventSystem 
{
    private $_handlers;

    public function __construct(array $_handlers = array())
    {
        $this->_handlers = $_handlers;
    }

    public function raiseEvent($event)
    {
        foreach ($this->_handlers as $handler) {
            if ($handler->handles($event->getName())) {
                $handler->handle($event->getParams());
            }
        }
        if (file_exists('application/eventHandlers/' . $event->getName() . '.php')) {
            $handlerClassName = 'eventHandlers\\' . $event->getName();
            $handler = new $handlerClassName();
            $handler->handle($event->getParams());
        }
    }

    public function addHandler($handler)
    {
        $this->_handlers[] = $handler;
    }
} 