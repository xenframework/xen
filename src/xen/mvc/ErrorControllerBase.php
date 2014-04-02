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

namespace xen\mvc;

/**
 * Class ErrorControllerBase
 *
 * Defines the methods for the ErrorController
 *
 * @package    xenframework
 * @subpackage xen\mvc
 * @author     Ismael Trascastro <itrascastro@xenframework.com>
 * @copyright  Copyright (c) xenFramework. (http://xenframework.com)
 * @license    MIT License - http://en.wikipedia.org/wiki/MIT_License
 * @link       https://github.com/xenframework/xen
 * @since      Class available since Release 1.0.0
 */
abstract class ErrorControllerBase extends Controller
{
    /**
     * exceptionHandlerAction
     *
     * Used when a new Exception is raised
     * A new layout is recommended
     *
     * @return mixed
     */
    abstract function exceptionHandlerAction();

    /**
     * pageNotFoundAction
     *
     * Used in a 404 Page Not Found error
     *
     * @return mixed
     */
    abstract function pageNotFoundAction();

    /**
     * forbiddenAction
     *
     * Used by the ACL when trying to access to a restricted area
     *
     * @return mixed
     */
    abstract function forbiddenAction();
}
