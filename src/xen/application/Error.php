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

namespace xen\application;
use xen\http\Response;

require str_replace('/', DIRECTORY_SEPARATOR, 'vendor/xen/http/Response.php');

/**
 * Class Error
 *
 * Manages all the Uncaught Exception in the core and returns an Error response
 *
 * @package    xenframework
 * @subpackage xen\application
 * @author     Ismael Trascastro <itrascastro@xenframework.com>
 * @copyright  Copyright (c) xenFramework. (http://xenframework.com)
 * @license    MIT License - http://en.wikipedia.org/wiki/MIT_License
 * @link       https://github.com/xenframework/xen
 * @since      Class available since Release 1.0.0
 */
class Error
{
    /**
     * @var Response
     */
    private $_response;

    /**
     * __construct
     *
     * Set the exception handler method
     */
    public function __construct()
    {
        $this->_response = new Response();

        $this->setExceptionHandler();
    }

    /**
     * setExceptionHandler
     *
     * Set the exception handler method
     *
     */
    public function setExceptionHandler()
    {
        set_exception_handler(array($this, 'coreExceptionHandler'));
    }

    /**
     * coreExceptionHandler
     *
     * This is the Exception Handler method
     * Creates an Error response with a 500 error code
     * No output buffering needed because the response we are still in the core
     *
     * @param \Exception $e
     *
     * @return Response
     */
    public function coreExceptionHandler(\Exception $e)
    {
        $content    = $this->_errorView($e);
        $statusCode = 500;

        $this->_response->setStatusCode($statusCode);
        $this->_response->setContent($content);

        return $this->_response->send();
    }

    private function _errorView(\Exception $e)
    {
        $title           = 'Error 500 - Uncaught Exception';
        $description     = 'Error - ' . $e->getMessage();

        $html = '
            <!DOCTYPE html>
            <html>
            <head>
                <title>' . $title . '</title>
                <meta charset="utf-8">
                <meta name="description" content="' . $description . '">
            </head>
            <body>
                <div>
                    <h1>' . $title . '</h1>
                    <table>
                        <tr>
                            <td><strong>Message:</strong></td>
                            <td>' . $e->getMessage() . '</td>
                        </tr>
                        <tr>
                            <td><strong>Code:</strong></td>
                            <td>' . $e->getCode() . '</td>
                        </tr>
                        <tr>
                            <td><strong>File:</strong></td>
                            <td>' . $e->getFile() . '</td>
                        </tr>
                        <tr>
                            <td><strong>Line:</strong></td>
                            <td>' . $e->getLine() . '</td>
                        </tr>
                        <tr>
                            <td><strong>Trace:</strong></td>
                            <td>' . preg_replace("/\n/", '<br>', $e->getTraceAsString()) . '</td>
                        </tr>
                    </table>
                </div>
            </body>
            </html>
        ';

        return $html;
    }
} 