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

namespace xen\db\doctrine;

require_once __DIR__ . '/../../../../../../../vendor/autoload.php';

use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;

class DoctrineBootstrap
{
    public static function bootstrap($dbConfig, $package, $applicationMode = 'development')
    {
        $packagePath = str_replace('\\', DIRECTORY_SEPARATOR, $package);

        $paths = array(__DIR__ . '/../../../../../../../application/packages/' . $packagePath . '/models/entities');
        $isDevMode = ($applicationMode == 'development') ? true : false;

        $dbParams = array(
            'driver'   => $dbConfig->driver,
            'user'     => $dbConfig->username,
            'password' => $dbConfig->password,
            'dbname'   => $dbConfig->dbname,
            'charset'  => $dbConfig->charset,
        );

        if ($applicationMode == "development")
        {
            $cache = new \Doctrine\Common\Cache\ArrayCache;
        }
        else
        {
            $cache = new \Doctrine\Common\Cache\ApcCache;
        }

        $config = Setup::createConfiguration($isDevMode);
        $driver = new AnnotationDriver(new AnnotationReader(), $paths);

        // registering noop annotation autoloader - allow all annotations by default
        AnnotationRegistry::registerLoader('class_exists');
        $config->setMetadataDriverImpl($driver);

        $proxyDir       = __DIR__ . '/../../../../../../../application/packages/' . $packagePath . '/models/proxies';
        $proxyNamespace = $package . '\\models\\proxies\\';

        $config->setProxyDir($proxyDir);
        $config->setProxyNamespace($proxyNamespace);

        $config->setMetadataCacheImpl($cache);
        $config->setQueryCacheImpl($cache);

        if ($applicationMode == "development")
        {
            $config->setAutoGenerateProxyClasses(true);
        }
        else
        {
            $config->setAutoGenerateProxyClasses(false);
        }

        return EntityManager::create($dbParams, $config);
    }
} 