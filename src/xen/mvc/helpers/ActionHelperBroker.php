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
 * Class ActionHelperBroker
 *
 * The ActionHelperBroker Factory
 *
 * @package    xenframework
 * @subpackage xen\mvc\helpers
 * @author     Ismael Trascastro <itrascastro@xenframework.com>
 * @copyright  Copyright (c) xenFramework. (http://xenframework.com)
 * @license    MIT License - http://en.wikipedia.org/wiki/MIT_License
 * @link       https://github.com/xenframework/xen
 * @since      Class available since Release 1.0.0
 */
class ActionHelperBroker extends HelperBroker
{
    /**
     * __construct
     *
     * set the namespaces and the paths for the Action Helpers
     */
    public function __construct($_package)
    {
        $this->_libNamespace = 'xen\\mvc\\helpers\\actionHelpers\\';
        $this->_appNamespace = $_package . '\\controllers\\helpers\\';
        $this->_libPath      = 'vendor/xenframework/xen/src/xen/mvc/helpers/actionHelpers/';
        $this->_appPath      = 'application/packages/' . str_replace('\\', DIRECTORY_SEPARATOR, $_package) . '/controllers/helpers/';
    }

    public function getHelper($helper, $params=array())
    {
        parent::getHelper($helper, $params);

        return new $this->_helperClassName($this->_helperParams);
    }
}
