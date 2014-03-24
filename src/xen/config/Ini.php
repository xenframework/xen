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
 * Class Ini
 *
 * Converts an .ini file into an object
 *
 * @package    xenframework
 * @subpackage xen\config
 * @author     Ismael Trascastro <itrascastro@xenframework.com>
 * @copyright  Copyright (c) xenFramework. (http://xenframework.com)
 * @license    MIT License - http://en.wikipedia.org/wiki/MIT_License
 * @link       https://github.com/xenframework/xen
 * @since      Class available since Release 1.0.0
 */
class Ini extends Config
{
    /**
     * __construct
     *
     * Creates the object representation of an .ini file section
     *
     * To do that:
     *
     *      1. Creates an array from .ini file using php defined function parse_ini_file
     *      2. Apply the sections inheritance
     *      3. Dotted properties are converted to array
     *      4. Selects the section from the array
     *      5. Creates the object for that section calling the parent constructor
     *
     * @param array     $file
     * @param string    $section
     */
    public function __construct($file, $section)
    {
        $array = parse_ini_file($file, true);

        $this->_applyExtends($array);

        $this->_convertDottedPropertiesToArray($array);

        $array = (array_key_exists($section, $array)) ? $array[$section] : array();

        parent::__construct($array);
    }

    /**
     * _applyExtends
     *
     * If a section inherits from another section, the inherited properties are copied from the parent
     *
     * For each section in the array
     *
     *      1. Gets the parents for that section
     *      2. For each parent
     *          2.1. Gets the parent array
     *          2.2. Copies the parent array values into this section
     *      3. The section name is changed removing the parents from it
     *
     * @param array $array
     */
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

    /**
     * _changeSectionName
     *
     * Removes parents from the section name
     *
     *      section3 : section2 : section 1     ===> section3
     *
     * If the section has parents then creates a new key in the array and unset the old
     *
     * @param array     $array
     * @param string    $section
     */
    private function _changeSectionName(&$array, $section)
    {
        $sectionName = explode(':', $section);

        if (sizeof($sectionName) > 1) {

            $newSectionName = trim($sectionName[0]);
            $array[$newSectionName] = $array[$section];
            unset($array[$section]);
        }
    }

    /**
     * _getParentArray
     *
     * Given a section name and an array, returns the sub array with that section name
     *
     * @param string $parent
     * @param array  $array
     *
     * @throws \Exception
     * @return array
     */
    private function _getParentArray($parent, $array)
    {
        foreach ($array as $section => $arraySection) {

            $sectionName = explode(':', $section);

            if (trim($sectionName[0]) == $parent) return $arraySection;
        }

        throw new \Exception('No section matches with ' . $parent);
    }

    /**
     * _getExtends
     *
     * Returns the parents for a given section
     *
     *      Removes the section from the section name and returns an array of the parents with the rest of the sections
     *
     * @param string $section
     *
     * @return array
     */
    private function _getExtends($section)
    {
        $sections = explode(':', $section);
        $extends = array_slice($sections, 1);
        $trimExtends = array_map('trim', $extends);

        return $trimExtends;
    }

    /**
     * _copySection
     *
     * Copies one section into another
     *
     *      If a key from source section does not exist in the target section, it is copied
     *
     * @param array $source
     * @param array $target
     */
    private function _copySection($source, &$target)
    {
        foreach ($source as $key => $value) {

            if (!array_key_exists($key, $target)) $target[$key] = $value;
        }
    }

    /**
     * _convertDottedPropertiesToArray
     *
     * For each property inside of each section:
     *
     *      1. Converts that dotted property into an array
     *      2. All the converted arrays in a section are merged
     *
     * @param array $array
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
     * _dotToArray
     *
     * Converts a dotted property into an array recursively
     *
     * This dotted property
     *
     *      'x.y.z' => $value
     *
     * will be converted into this array
     *
     *      array(
     *              'x' => array(
     *                      'y' => array(
     *                              'z' => $value
     *                   )
     *              )
     *      )
     *
     * @param string    $dottedKey
     * @param mixed     $value The value for that dotted key in the array
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
