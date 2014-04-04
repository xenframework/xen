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

namespace xen\mvc\view;

use xen\mvc\helpers\ViewHelperBroker;
use xen\application\Router;
use xen\mvc\view\exception\PartialNotFoundException;

/**
 * Class Phtml
 *
 * The View
 *
 * All view objects are phtml files.
 * A phtml file can have partials that are also phtml files
 * Only one restriction: partial 'content' is mandatory in the layout. This is the View
 *
 * @package    xenframework
 * @subpackage xen\mvc\view
 * @author     Ismael Trascastro <itrascastro@xenframework.com>
 * @copyright  Copyright (c) xenFramework. (http://xenframework.com)
 * @license    MIT License - http://en.wikipedia.org/wiki/MIT_License
 * @link       https://github.com/xenframework/xen
 * @since      Class available since Release 1.0.0
 */
class Phtml
{
    /**
     * @var string Path to the file
     */
    private $_file;

    /**
     * @var array The partials
     */
    private $_partials;

    /**
     * @var ViewHelperBroker To call view helpers
     */
    private $_viewHelperBroker;

    /**
     * @var Router To generate links
     */
    private $_router;

    /**
     * __construct
     *
     * @param string $_file The .phtml file associated to this view or layout
     */
    function __construct($_file)
    {
        $this->_file        = $_file;
        $this->_partials    = array();
    }

    /**
     * setFile
     *
     * @param string $_file
     */
    public function setFile($_file)
    {
        $this->_file = $_file;
    }

    /**
     * getFile
     *
     * @return string
     */
    public function getFile()
    {
        return $this->_file;
    }

    /**
     * setViewHelperBroker
     *
     * @param ViewHelperBroker $viewHelperBroker
     */
    public function setViewHelperBroker($viewHelperBroker)
    {
        $this->_viewHelperBroker = $viewHelperBroker;
    }

    /**
     * getViewHelperBroker
     *
     * @return ViewHelperBroker
     */
    public function getViewHelperBroker()
    {
        return $this->_viewHelperBroker;
    }

    /**
     * setPartials
     *
     * @param array $_partials
     */
    public function setPartials($_partials)
    {
        $this->_partials = $_partials;
    }

    /**
     * getPartials
     *
     * @return array
     */
    public function getPartials()
    {
        return $this->_partials;
    }

    /**
     * addPartials
     *
     * @param array $_partials
     */
    public function addPartials($_partials)
    {
        foreach ($_partials as $partial => $value) {

            $this->_partials[$partial] = $value;
        }
    }

    /**
     * addPartial
     *
     * @param string $partial The partial name
     * @param Phtml $value
     */
    public function addPartial($partial, $value)
    {
        $this->_partials[$partial] = $value;
    }

    /**
     * partial
     *
     * @param string $partial
     *
     * @throws exception\PartialNotFoundException
     * @return Phtml
     */
    public function partial($partial)
    {
        if ($this->partialExists($partial)) return $this->_partials[$partial];

        throw new PartialNotFoundException($partial . ' partial does not exist');
    }

    public function partialExists($partial)
    {
        return array_key_exists($partial, $this->_partials);
    }

    /**
     * out
     *
     * Sanitize the output to prevent xss
     *
     * @param string $string
     *
     * @return string
     */
    public function out($string)
    {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }

    /**
     * setRouter
     *
     * @param Router $router
     */
    public function setRouter($router)
    {
        $this->_router = $router;
    }

    /**
     * getRouter
     *
     * @return Router
     */
    public function getRouter()
    {
        return $this->_router;
    }

    /**
     * render
     *
     * In Bootstrap ViewHelperBroker is set to the very first view, the layout
     * ViewHelperBroker will be passed to the child partials in this render() method, the same
     * as the view variables of every Phtml => At this point no more variables can be added to this Phtml
     *
     * The View variables are the public properties
     * If a property does not exist in a child partial, it will be propagated to it
     *
     * ViewHelperBroker and Router are also propagated to its child
     */
    public function render()
    {
        $reflect = new \ReflectionObject($this);
        $properties = $reflect->getProperties(\ReflectionProperty::IS_PUBLIC);

        foreach ($this->getPartials() as $partial) {

            foreach ($properties as $property) {

                $propertyName = $property->getName();
                if (!isset($partial->$propertyName)) $partial->$propertyName = $this->$propertyName;

            }

            $partial->setViewHelperBroker($this->_viewHelperBroker);
            $partial->setRouter($this->_router);
        }

        require $this->_file;
    }
}
