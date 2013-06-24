<?php

namespace Rhapsody;

class Rhapsody
{
    private static $conn;
    private static $modelFormatter;

    /**
     * Setup connection
     *
     * @param  array $parameters Connection parameters
     *
     *                  array(
     *                      'dbname' => 'mydb',
     *                      'user' => 'user',
     *                      'password' => 'secret',
     *                      'host' => 'localhost',
     *                      'driver' => 'pdo_mysql',
     *                  );
     */
    public static function setup($parameters)
    {
        $config = new \Doctrine\DBAL\Configuration();
        self::$conn = \Doctrine\DBAL\DriverManager::getConnection($parameters, $config);
    }


    public static function setModelFormatter($string)
    {
        self::$modelFormatter = $string;
    }

    public static function createObject($table, array $data = array())
    {
        $object = null;

        if (self::$modelFormatter) {
            $class = self::$modelFormatter.'\\'.ucfirst($table);
            if (class_exists($class)) {
                $object = new $class($table, $data);
            } else {
                $object = new Oject($table, $data);
            }
        } else {
            $object = new Oject($table, $data);
        }

        return $object;
    }

    public static function getConnection()
    {
        return self::$conn;
    }
}
