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

namespace xen\kernel;
use xen\kernel\exception\CacheDirNotWritableException;
use xen\kernel\exception\CacheEmptyFileException;
use xen\kernel\exception\CacheUnableToLockFileException;
use xen\kernel\exception\CacheUnableToOpenFileException;

/**
 * Class Cache
 *
 * Can cache complete url's or partials
 *
 * @package    xenframework
 * @subpackage xen\kernel
 * @author     Ismael Trascastro <itrascastro@xenframework.com>
 * @copyright  Copyright (c) xenFramework. (http://xenframework.com)
 * @license    MIT License - http://en.wikipedia.org/wiki/MIT_License
 * @link       https://github.com/xenframework/xen
 * @since      Class available since Release 1.0.0
 */
class Cache 
{
    /**
     * @var string The directory used to store the cached content
     */
    private $_dir;

    /**
     * @param string $_dir
     */
    public function __construct($_dir)
    {
        $this->_dir = $_dir;
    }

    /**
     * _urlToFile
     *
     * MD5 Encrypt the url or the partial file route to generate a file name
     *
     * @param string $url
     *
     * @return string
     */
    private function _urlToFile($url)
    {
        return md5($url) . '.html';
    }

    /**
     * get
     *
     * Get content from the cache
     *
     * @param string $url     Can be a partial
     * @param int    $expires Time in seconds
     *
     * @throws exception\CacheUnableToOpenFileException
     * @throws exception\CacheEmptyFileException
     * @return bool|mixed The cached content. False if it is not found or it is too old
     */
    public function get($url, $expires)
    {
        $file = $this->_dir . '/' . $this->_urlToFile($url);

        if (!file_exists($file)) return false;

        if (filemtime($file) < (time() - $expires)) return false;

        if (!$fp = fopen($file, 'rb'))
            throw new CacheUnableToOpenFileException('Cache ' . $file . ' can not be opened.');

        flock($fp, LOCK_SH);

        if (!filesize($file) > 0) throw new CacheEmptyFileException('Cache ' . $file . ' is empty.');

        $cache = unserialize(fread($fp, filesize($file)));

        flock($fp, LOCK_UN);
        fclose($fp);

        return $cache;
    }

    /**
     * put
     *
     * Save the content in the cache
     *
     * @param string $url     The url or the partial to be cached
     * @param string $content The content
     *
     * @throws exception\CacheUnableToLockFileException
     * @throws exception\CacheUnableToOpenFileException
     * @throws exception\CacheDirNotWritableException
     * @return bool true if everything goes well
     */
    public function put($url, $content)
    {
        if ( !is_dir($this->_dir) || !is_writable($this->_dir))
            throw new CacheDirNotWritableException($this->_dir . ' directory is either not found or not writable.');

        $file = $this->_dir . '/' . $this->_urlToFile($url);

        if ( ! $fp = fopen($file, 'wb'))
            throw new CacheUnableToOpenFileException('Cache ' . $file . ' can not be opened.');

        if (flock($fp, LOCK_EX))
        {
            $content =
                '<!-- Cached Content Start -->' . PHP_EOL . $content . PHP_EOL .
                '<!-- Cached Content End :: Generated ' . date('l jS \of F Y h:i:s A', filemtime($file)) . ' -->';
            fwrite($fp, serialize($content));
            flock($fp, LOCK_UN);
        }
        else
        {
            throw new CacheUnableToLockFileException('Cache unable to lock file ' . $file);
        }

        fclose($fp);

        chmod($file, 0777);

        return true;
    }

    /**
     * remove
     *
     * Removes the content from the cache
     *
     * @param string $url
     *
     * @return bool false if the content does not exist
     */
    public function remove($url)
    {
        $file = $this->_dir . '/' . $this->_urlToFile($url);

        if (file_exists($file))
        {
            unlink($file);

            return true;
        }

        return false;
    }
}
