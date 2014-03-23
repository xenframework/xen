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

namespace xen\config;

/**
 * Class Config
 *
 * Converts an array into an object
 *
 * @package    xenframework
 * @subpackage xen\config
 * @author     Ismael Trascastro <itrascastro@xenframework.com>
 * @copyright  Copyright (c) xenFramework. (http://xenframework.com)
 * @license    MIT License - http://en.wikipedia.org/wiki/MIT_License
 * @link       https://github.com/xenframework/xen
 * @since      Class available since Release 1.0.0
 */
class Config 
{
    /**
     * __construct
     *
     * Recursively looks for arrays and coverts them into objects. If a key value is not an array then it will be
     * set as a property in the new object
     *
     * @param array $array The config array
     */
    public function __construct($array)
    {
        foreach($array as $key => $value)
        {
            $this->$key = (!is_array($value)) ? $value : new Config($value);
        }
    }

}
