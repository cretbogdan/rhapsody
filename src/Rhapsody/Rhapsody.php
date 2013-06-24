<?php

namespace Rhapsody;

class Rhapsody
{
    private static $conn;

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
    public function setup($parameters)
    {
        $config = new \Doctrine\DBAL\Configuration();
        self::$conn = \Doctrine\DBAL\DriverManager::getConnection($parameters, $config);
    }


    public static function getConnection()
    {
        return self::$conn;
    }
}
