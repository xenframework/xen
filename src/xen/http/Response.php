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

namespace xen\http;

/**
 * Class Response
 *
 * The Response
 *
 * @package    xenframework
 * @subpackage xen\http
 * @author     Ismael Trascastro <itrascastro@xenframework.com>
 * @copyright  Copyright (c) xenFramework. (http://xenframework.com)
 * @license    MIT License - http://en.wikipedia.org/wiki/MIT_License
 * @link       https://github.com/xenframework/xen
 * @since      Class available since Release 1.0.0
 */
class Response 
{
    /**
     * @var string The Response headers
     */
    private $_headers;

    /**
     * @var string The Response content
     */
    private $_content;

    /**
     * @var int The Response status code
     */
    private $_statusCode;

    /**
     * __construct
     *
     * The status code is not set at this point
     */
    public function __construct()
    {
        $this->_statusCode = false;
    }

    /**
     * send
     *
     * Set the Response status code and echoes the Response content
     *
     * @return $this The Response
     */
    public function send()
    {
        http_response_code($this->_statusCode);

        echo $this->_content;

        return $this;
    }

    /**
     * setContent
     *
     * @param string $content
     */
    public function setContent($content)
    {
        $this->_content = $content;
    }

    /**
     * getContent
     *
     * @return string
     */
    public function getContent()
    {
        return $this->_content;
    }

    /**
     * setHeaders
     *
     * @param string $headers
     */
    public function setHeaders($headers)
    {
        $this->_headers = $headers;
        header($this->_headers);
    }

    /**
     * getHeaders
     *
     * @return string
     */
    public function getHeaders()
    {
        return $this->_headers;
    }

    /**
     * setStatusCode
     *
     * @param int $statusCode
     */
    public function setStatusCode($statusCode)
    {
        $this->_statusCode = $statusCode;
    }

    /**
     * getStatusCode
     *
     * @return bool|int False if it is not set
     */
    public function getStatusCode()
    {
        return $this->_statusCode;
    }

}
