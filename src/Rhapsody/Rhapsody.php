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
    private static $objectCache;

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
        self::$conn->getConfiguration()->setSQLLogger($logger);
    }

    public static function enableQueryLogger()
    {
        self::setQueryLogger(new DebugStack());
    }

    public static function disableQueryLogger()
    {
        self::setQueryLogger(null);
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
        $cache = self::getObjectCache();

        if (isset($data['id']) && $cache->containsObject($data['id'], $table)) {
            $object = $cache->fetchObject($data['id'], $table);
        } else {
            $class = self::getObjectClass($table);
            $object = new $class(Inflector::tableize($table), $data);
            $cache->saveObject($object);
        }

        return $object;
    }

    public static function createCollection($table, array $rows = array(), $isNew = true)
    {
        $table = Inflector::tableize($table);

        return Collection::create($table, $rows, $isNew);
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

    public static function getObjectCache()
    {
        if (null == self::$objectCache) {
            self::$objectCache = new ObjectCache();
        }

        return self::$objectCache;
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
        $logger = self::$conn->getConfiguration()->getSQLLogger();

        if (! $logger) {
            throw new RhapsodyException("No SQL Logger is enabled!");
        }

        return end($logger->queries);
    }

    /**
     * Get total number of queries
     *
     * @return int
     */
    public static function getTotalQueries()
    {
        $logger = self::$conn->getConfiguration()->getSQLLogger();

        if (! $logger) {
            throw new RhapsodyException("No SQL Logger is enabled!");
        }

        return $logger->currentQuery;
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
        $objectClass = self::getDefaultObjectClass();

        if (self::$modelFormatter) {
            $class = self::$modelFormatter.'\\'.Inflector::classify($table);

            if (class_exists($class)) {
                $objectClass = $class;
            }
        }

        return $objectClass;
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
        $queryClass = '\Rhapsody\Query';

        if (self::$modelFormatter) {
            $class = self::$modelFormatter.'\\'.Inflector::classify($table.'Query');

            if (class_exists($class)) {
                $queryClass = $class;
            }
        }

        return $queryClass;
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
