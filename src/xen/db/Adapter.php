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

namespace xen\db;

/**
 * Class Adapter
 *
 * Creates a PDO object depending on the given driver in the dbConfig
 *
 * @package    xenframework
 * @subpackage xen\db
 * @author     Ismael Trascastro <itrascastro@xenframework.com>
 * @copyright  Copyright (c) xenFramework. (http://xenframework.com)
 * @license    MIT License - http://en.wikipedia.org/wiki/MIT_License
 * @link       https://github.com/xenframework/xen
 * @since      Class available since Release 1.0.0
 */
class Adapter extends \PDO
{
    /**
     * __construct
     *
     * Creates a PDO object depending on the given driver in the dbConfig
     *
     * @param $dbConfig
     *
     * @throws \Exception From \PDO __construct
     */
    function __construct($dbConfig)
    {
        switch (strtolower($dbConfig->driver)) {

            case 'mysql': //MySQL

                $dsn = 'mysql:host=' . $dbConfig->hostname;

                if (isset($dbConfig->port)) $dsn .= ';port=' . $dbConfig->port;

                $dsn .= ';dbname=' . $dbConfig->dbname;

                if (isset($dbConfig->charset)) $dsn .= ';charset=' . $dbConfig->charset;

                parent::__construct($dsn, $dbConfig->username, $dbConfig->password);

                break;

            case 'pgsql': //PostgreSQL

                $dsn = 'pgsql:host=' . $dbConfig->hostname;

                if (isset($dbConfig->port)) $dsn .= ';port=' . $dbConfig->port;

                $dsn .= ';dbname=' . $dbConfig->dbname;
                $dsn .= ';user=' . $dbConfig->username;
                $dsn .= ';password=' . $dbConfig->password;

                parent::__construct($dsn);

                break;

            case 'dblib': //MS Sql

                $dsn = 'dblib:host=' . $dbConfig->hostname;

                if (isset($dbConfig->port)) $dsn .= ',' . $dbConfig->port;

                $dsn .= ';Database=' . $dbConfig->dbname;
                $dsn .= ',' . $dbConfig->username;
                $dsn .= ',' . $dbConfig->password;

                parent::__construct($dsn);

                break;
        }
    }
}
