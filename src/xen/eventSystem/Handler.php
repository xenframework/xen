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
 * The object who handles an Event
 *
 * They are defined in 'application/eventHandlers' directory
 * All of them extends this class (they have to define the 'handle' method)
 *
 * A Handler can handle more than one events
 *
 * @package    xenframework
 * @subpackage xen\eventSystem
 * @author     Ismael Trascastro <itrascastro@xenframework.com>
 * @copyright  Copyright (c) xenFramework. (http://xenframework.com)
 * @license    MIT License - http://en.wikipedia.org/wiki/MIT_License
 * @link       https://github.com/xenframework/xen
 * @since      Class available since Release 1.0.0
 */
abstract class Handler
{
    /**
     * @var array The events that are handled by this handler
     */
    private $_events;

    /**
     * __construct
     *
     * @param array $_events
     */
    public function __construct($_events = array())
    {
        $this->_events = $_events;
    }

    /**
     * addHandles
     *
     * Add events to be handled by this handler
     *
     * @param array $events
     */
    public function addHandles($events)
    {
        $this->_events = array_merge($this->_events, $events);
    }

    /**
     * handles
     *
     * Used to know if this handler handle an event
     *
     * @param $event
     *
     * @return bool
     */
    public function handles($event)
    {
        return in_array($event, $this->_events);
    }

    /**
     * handle
     *
     * This method contains the code who handles the event
     *
     * @param $params
     */
    public abstract function handle($params);
}
