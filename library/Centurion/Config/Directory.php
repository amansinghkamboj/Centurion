<?php
/**
 * Centurion
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@centurion-project.org so we can send you a copy immediately.
 *
 * @category    Centurion
 * @package     Centurion_Config
 * @subpackage  Directory
 * @copyright   Copyright (c) 2008-2011 Octave & Octave (http://www.octaveoctave.com)
 * @license     http://centurion-project.org/license/new-bsd     New BSD License
 * @version     $Id$
 */

/**
 * Inspired by sfConfig of Symfony project.
 *
 * @category    Centurion
 * @package     Centurion_Config
 * @subpackage  Directory
 * @copyright   Copyright (c) 2008-2011 Octave & Octave (http://www.octaveoctave.com)
 * @license     http://centurion-project.org/license/new-bsd     New BSD License
 * @author      Laurent Chenay <lc@octaveoctave.com>
 * @todo        Documentation
 */
class Centurion_Config_Directory
{
    protected static $_environment = null;

    public static function mergeArrays($Arr1, $Arr2)
    {
      foreach($Arr2 as $key => $value) {
          if (is_string($key)) {
            if (array_key_exists($key, $Arr1) && is_array($value)) {
              $Arr1[$key] = self::mergeArrays($Arr1[$key], $Arr2[$key]);
            } else {
              $Arr1[$key] = $value;
            }
          } else {
              $Arr1[] = $value;
          }
    
      }
    
      return $Arr1;
    
    }
    public static function loadConfig($path, $environment, $recursivelyLoadModuleConfig = false)
    {
        self::$_environment = $environment;

        if (is_string($path) && is_dir($path)) {
            $config = array();

            $iterator = new Centurion_Iterator_Directory($path);
            $tabFile = array();
            foreach ($iterator as $file) {
                if ($file->isDot())
                    continue;
                $tabFile[$file->getPathName()] = $file->getPathName();
            }
            
            ksort($tabFile);

            foreach($tabFile as $key => $file) {
                $suffix = strtolower(pathinfo($file, PATHINFO_EXTENSION));

                switch ($suffix) {
                    case 'ini':
                    case 'xml':
                    case 'php':
                    case 'inc':
                        $result = self::_loadConfig($file);
                        $config = self::mergeArrays($config, $result);
                        //$config = array_merge_recursive($config, $result);
                }
            }

            if ($recursivelyLoadModuleConfig && isset($config['resources']) && isset($config['resources']['modules'])) {
                foreach ($config['resources']['modules'] as $module) {
                    $dir = null;
                    
                    if (file_exists(APPLICATION_PATH . '/../library/Centurion/Contrib/' . $module . '/configs')) {
                        $dir = APPLICATION_PATH . '/../library/Centurion/Contrib/' . $module . '/configs';
                    } else  if (file_exists(APPLICATION_PATH . '/modules/' . $module . '/configs')) {
                        $dir = APPLICATION_PATH . '/modules/' . $module . '/configs';
                    }

                    if (null !== $dir) {
                        $result = self::loadConfig($dir, $environment);
                        $config = self::mergeArrays($result, $config);
                    }
                }
            }

            return $config;
        }
        throw new Exception('Path must be a directory', 500);
    }

    /**
     * @see Zend_Application->_loadConfig();
     */
    protected static function _loadConfig($file)
    {
        $environment = self::$_environment;
        $suffix      = strtolower(pathinfo($file, PATHINFO_EXTENSION));

        switch ($suffix) {
            case 'ini':
                $config = new Zend_Config_Ini($file, $environment);
                break;

            case 'xml':
                $config = new Zend_Config_Xml($file, $environment);
                break;

            case 'php':
            case 'inc':
                $config = include $file;
                if (!is_array($config)) {
                    throw new Zend_Application_Exception('Invalid configuration file provided; PHP file does not return array value');
                }
                return $config;
                break;

            default:
                throw new Zend_Application_Exception('Invalid configuration file provided; unknown config type');
        }

        return $config->toArray();
    }
}