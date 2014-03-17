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

/**
 * Class File
 *
 * @package xen\mvc\view
 * @author  Ismael Trascastro itrascastro@xenframework.com
 *
 *          A phtml file can have partials that are also phtml files
 *
 *          Only one restriction: partial 'content' is mandatory in the layout
 */
class Phtml
{
    private $_file;
    private $_partials;
    private $_viewHelperBroker;
    private $_router;

    function __construct($_file)
    {
        $this->_file        = $_file;
        $this->_partials    = array();
    }

    /**
     * @param mixed $_file
     */
    public function setFile($_file)
    {
        $this->_file = $_file;
    }

    /**
     * @return mixed
     */
    public function getFile()
    {
        return $this->_file;
    }

    /**
     * @param mixed $viewHelperBroker
     */
    public function setViewHelperBroker($viewHelperBroker)
    {
        $this->_viewHelperBroker = $viewHelperBroker;
    }

    /**
     * @return mixed
     */
    public function getViewHelperBroker()
    {
        return $this->_viewHelperBroker;
    }

    /**
     * @param mixed $_partials
     */
    public function setPartials($_partials)
    {
        $this->_partials = $_partials;
    }

    /**
     * @return mixed
     */
    public function getPartials()
    {
        return $this->_partials;
    }

    /**
     * We propagate phtml variables to partials and also inject the ViewHelperBroker
     * @param array $_partials Is an associative array ('name' => $partial)
     */
    public function addPartials($_partials)
    {
        foreach ($_partials as $name => $partial) {
            $this->_partials[$name] = $partial;
        }
    }

    public function addPartial($name, $value)
    {
        $this->_partials[$name] = $value;
    }

    public function partial($name)
    {
        return $this->_partials[$name];
    }

    public function out($string)
    {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }

    /**
     * @param mixed $router
     */
    public function setRouter($router)
    {
        $this->_router = $router;
    }

    /**
     * @return mixed
     */
    public function getRouter()
    {
        return $this->_router;
    }

    /**
     * In Bootstrap we set ViewHelperBroker to the very first view, the layout
     * ViewHelperBroker will be passed to the child in the render() method
     * as the view variables of every Phtml => At this point no more variables can be added to this phtml
     *
     */
    public function render()
    {
        $reflect = new \ReflectionObject($this);
        $properties = $reflect->getProperties(\ReflectionProperty::IS_PUBLIC);
        foreach ($this->getPartials() as $partial) {
            foreach ($properties as $property) {
                $propertyName = $property->getName();
                $partial->$propertyName = $this->$propertyName;
            }
            $partial->setViewHelperBroker($this->_viewHelperBroker);
            $partial->setRouter($this->_router);
        }

        require $this->_file;
    }
}
