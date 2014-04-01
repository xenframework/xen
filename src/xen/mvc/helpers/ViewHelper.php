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

namespace xen\mvc\helpers;

/**
 * Class ViewHelper
 *
 * ViewHelper Abstraction
 *
 * @package    xenframework
 * @subpackage xen\mvc\helpers
 * @author     Ismael Trascastro <itrascastro@xenframework.com>
 * @copyright  Copyright (c) xenFramework. (http://xenframework.com)
 * @license    MIT License - http://en.wikipedia.org/wiki/MIT_License
 * @link       https://github.com/xenframework/xen
 * @since      Class available since Release 1.0.0
 */
abstract class ViewHelper
{
    /**
     * @var string The ViewHelper html generated
     */
    protected $_html;

    /**
     * __construct
     *
     * The ViewHelper constructor
     * It can receive params from the View
     *
     * @param array $params
     */
    abstract function __construct($params=array());

    /**
     * show
     *
     * Method used for retrieve the ViewHelper html
     *
     * @return string
     */
    public function show()
    {
        return $this->_html;
    }
}
