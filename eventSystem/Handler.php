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
 * Class Handler
 *
 * @package xen\eventSystem
 * @author  Ismael Trascastro itrascastro@xenframework.com
 *
 *          A Handler object is the VB handler function concept
 *          a function can handle events (>=1)1
 */
abstract class Handler
{
    private $_events;

    public function __construct(array $_events = array())
    {
        $this->_events = $_events;
    }

    public function addHandles(array $events)
    {
        $this->_events = array_merge($this->_events, $events);
    }

    public function handles($event)
    {
        return in_array($event, $this->_events);
    }

    public abstract function handle($params);
}