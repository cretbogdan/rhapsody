<?php

namespace Rhapsody;

use Doctrine\Common\Util\Inflector;

class Rhapsody
{
    private static $conn;
    private static $modelFormatter;

    /**
     * Setup connection.
     * Accepts a Doctrine connection
     *
     * @param  array $parameters Connection parameters
     *                  array(
     *                      'doctrine_connection' => $conn,
     *                      'dbname' => 'mydb',
     *                      'user' => 'user',
     *                      'password' => 'secret',
     *                      'host' => 'localhost',
     *                      'driver' => 'pdo_mysql',
     *                  );
     */
    public static function setup($parameters)
    {
        if (isset($parameters['doctrine_connection'])) {
            self::$conn = $parameters['doctrine_connection'];
        } else {
            $config = new \Doctrine\DBAL\Configuration();
            self::$conn = \Doctrine\DBAL\DriverManager::getConnection($parameters, $config);
        }

        if (isset($parameters['model_formatter'])) {
            self::setModelFormatter($parameters['model_formatter']);
        }
    }


    public static function setModelFormatter($string)
    {
        self::$modelFormatter = $string;
    }

    public static function createObject($table, array $data = array())
    {
        $class = self::getObjectClass($table);

        return new $class($table, $data);
    }

    public static function createQueryBuilder()
    {
        return self::getConnection()->createQueryBuilder();
    }

    public static function getConnection()
    {
        return self::$conn;
    }

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

    public function getQueryClass($table)
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

    private static function getDefaultObjectClass()
    {
        return '\Rhapsody\Object';
    }
}
