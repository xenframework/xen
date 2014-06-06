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


class Cache 
{
    private $_dir;

    public function __construct($_dir)
    {
        $this->_dir = $_dir;
    }

    private function _urlToFile($url)
    {
        return md5($url) . '.html';
    }

    public function get($url, $expires)
    {
        $file = $this->_dir . '/' . $this->_urlToFile($url);

        if (!file_exists($file)) return false;

        if (filemtime($file) < (time() - $expires)) return false;

        if (!$fp = fopen($file, 'rb')) echo 'can not open'; //throw new \Exception('Cache ' . $file . ' can not be opened.');

        flock($fp, LOCK_SH);

        if (!filesize($file) > 0) echo 'empty file cache'; //throw new \Exception('Cache ' . $file . ' is empty.');

        $cache = unserialize(fread($fp, filesize($file)));

        flock($fp, LOCK_UN);
        fclose($fp);

        return $cache;
    }

    public function put($url, $content)
    {
        if ( !is_dir($this->_dir) || !is_writable($this->_dir)) return false;

        $file = $this->_dir . '/' . $this->_urlToFile($url);

        if ( ! $fp = fopen($file, 'wb')) return false;

        if (flock($fp, LOCK_EX))
        {
            fwrite($fp, serialize('<!-- Cached Content Start -->' . PHP_EOL . $content . PHP_EOL . '<!-- Cached Content End :: Generated ' . date('l jS \of F Y h:i:s A', filemtime($file)) . ' -->'));
            flock($fp, LOCK_UN);
        }
        else
        {
            return false;
        }

        fclose($fp);

        @chmod($file, 0777);

        return true;
    }

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