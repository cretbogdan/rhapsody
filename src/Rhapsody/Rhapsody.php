<?php

namespace Rhapsody;

use Doctrine\Common\Util\Inflector;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Logging\SQLLogger;
use Doctrine\DBAL\Logging\DebugStack;

class Rhapsody
{
    private static $conn;
    private static $tableManager;
    private static $modelFormatter;

    /**
     * Setup connection.
     * Accepts a Doctrine connection
     *
     * @param  array $parameters Connection parameters
     *    array(
     *        'doctrine_connection' => $conn,
     *        'dbname' => 'mydb',
     *        'user' => 'user',
     *        'password' => 'secret',
     *        'host' => 'localhost',
     *        'driver' => 'pdo_mysql',
     *        'model_formatter' => 'MyProject\Model'
     *    );
     */
    public static function setup($parameters)
    {
        if (isset($parameters['doctrine_connection'])) {
            if (! is_object($parameters['doctrine_connection']) || ! $parameters['doctrine_connection'] instanceof Connection) {
                throw new \InvalidArgumentException("Parameter doctrine_connection must be instance of \Doctrine\DBAL\Connection");
            }

            $conn = $parameters['doctrine_connection'];
        } else {
            $config = new \Doctrine\DBAL\Configuration();
            $conn = \Doctrine\DBAL\DriverManager::getConnection($parameters, $config);
        }

        Rhapsody::setConnection($conn);

        if (isset($parameters['model_formatter'])) {
            self::setModelFormatter($parameters['model_formatter']);
        }
    }


    public static function setQueryLogger(SQLLogger $logger = null)
    {
        if (null === $logger) {
            $logger = new Logger\EchoDebugStack();
        }

        self::$conn->getConfiguration()->setSQLLogger($logger);
    }


    /**
     * Set the model formatter for extending base classes
     *
     * @param  $string
     */
    public static function setModelFormatter($string)
    {
        self::$modelFormatter = $string;
    }


    /**
     * Create a new record object for given object
     *
     * @param  string $table Table/Model name
     * @param  array  $data  Populate date
     *
     * @return Object
     */
    public static function create($table, array $data = array())
    {
        $class = self::getObjectClass($table);

        return new $class(Inflector::tableize($table), $data);
    }


    /**
     * Create an instance of a query class for given table
     *
     * @param  string $table
     *
     * @return Query
     */
    public static function query($table)
    {
        return Query::create($table);
    }


    public static function getTableManager()
    {
        if (null == self::$tableManager) {
            self::$tableManager = new TableManager(self::$conn);
        }

        return self::$tableManager;
    }


    public static function setConnection(Connection $conn)
    {
        self::$conn = $conn;
    }


    /**
     * Return the doctrine connection
     *
     * @return Doctrine\DBAL\Connection
     */
    public static function getConnection()
    {
        return self::$conn;
    }


    /**
     * Get last executed query
     *
     * @return string
     */
    public static function getLastExecutedQuery()
    {
        return Query::getLastExecutedQuery();
    }


    /**
     * Get the object class for a given table
     *
     * @param  string $table
     *
     * @return string
     */
    public static function getObjectClass($table)
    {
        if (self::$modelFormatter) {
            $class = self::$modelFormatter.'\\'.Inflector::classify($table);

            if (class_exists($class)) {
                return $class;
            } else {
                return self::getDefaultObjectClass();
            }
        } else {
            return self::getDefaultObjectClass();
        }
    }


    /**
     * Get the query class for a given table
     *
     * @param  string $table
     *
     * @return string
     */
    public static function getQueryClass($table)
    {
        if (self::$modelFormatter) {
            $class = self::$modelFormatter.'\\'.Inflector::classify($table.'Query');

            if (class_exists($class)) {
                return $class;
            } else {
                return '\Rhapsody\Query';
            }
        } else {
            return '\Rhapsody\Query';
        }
    }


    /**
     * Get the default object class
     *
     * @return string
     */
    private static function getDefaultObjectClass()
    {
        return '\Rhapsody\Object';
    }
}
