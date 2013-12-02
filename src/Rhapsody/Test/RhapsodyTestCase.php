<?php

namespace Rhapsody\Test;

use Rhapsody;

class RhapsodyTestCase extends \PHPUnit_Framework_TestCase
{
    public static function setUpBeforeClass()
    {
        Rhapsody::setup(array(
            'dbname' => 'rhapsody_test',
            'user' => 'root',
            'password' => '123456',
            'host' => 'localhost',
            'driver' => 'pdo_mysql'
        ));

        Rhapsody::setQueryLogger();
    }
}
