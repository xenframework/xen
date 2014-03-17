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


class Ini extends Config
{
    //TODO folder has to be passed as a parameter
    public function __construct($file, $section)
    {
        $array = parse_ini_file('application/configs/' . $file, true);
        $this->_applyExtends($array);
        $this->_convertDottedPropertiesToArray($array);
        if (array_key_exists($section, $array)) {
            $array = $array[$section];
        } else {
            $array = array();
        }
        parent::__construct($array);
    }

    private function _applyExtends(&$array)
    {
        foreach ($array as $section => &$arraySection) {
            $extends = $this->_getExtends($section);
            foreach ($extends as $parent) {
                $parentArray = $this->_getParentArray($parent, $array);
                $this->_copySection($parentArray, $arraySection);
            }
            $this->_changeSectionName($array, $section);
        }
    }

    private function _changeSectionName(&$array, $section)
    {
        $sectionName = explode(':', $section);
        if (sizeof($sectionName) > 1) { //if 0 we will unset the entire section
            $newSectionName = trim($sectionName[0]);
            $array[$newSectionName] = $array[$section];
            unset($array[$section]);
        }
    }

    private function _getParentArray($parent, $array)
    {
        foreach ($array as $section => $arraySection) {
            $sectionName = explode(':', $section);
            if (trim($sectionName[0]) == $parent) {
                return $arraySection;
            }
        }
    }

    private function _getExtends($section)
    {
        $sections = explode(':', $section);
        $extends = array_slice($sections, 1);
        $trimExtends = array_map('trim', $extends);
        return $trimExtends;
    }

    /**
     * Copies one section into another one
     *
     * @param $source
     * @param $target
     */
    private function _copySection($source, &$target)
    {
        foreach ($source as $key => $value) {
            if (!array_key_exists($key, $target)) {
                $target[$key] = $value;
            }
        }
    }

    /**
     * Converts every property into an array and then does a recursive merge
     *
     * @param $array
     */
    private function _convertDottedPropertiesToArray(&$array)
    {
        foreach ($array as $section => $sectionArray) {
            $merged = array();
            foreach ($sectionArray as $key => $value) {
                $property = $this->_dotToArray($key, $value);
                $merged = array_merge_recursive($merged, $property);
            }
            $array[$section] = $merged;
        }
    }

    /**
     * Converts a dotted key into an array
     *
     * 'x.y.z' => 3
     *
     * array(
     *      'x' => array(
     *              'y' => array(
     *                      'z' => 3
     *              )
     *      )
     * )
     *
     * @param $dottedKey
     * @param $value
     *
     * @return array
     */
    private function _dotToArray($dottedKey, $value)
    {
        $property = explode('.', $dottedKey);
        if (sizeof($property) == 1) {
            return array($property[0] => $value);
        } else {
            return array($property[0] => $this->_dotToArray(implode('.', array_slice($property, 1)), $value));
        }
    }
} 