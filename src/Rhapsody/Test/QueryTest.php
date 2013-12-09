<?php

namespace Rhapsody\Test;

use Rhapsody;
use Rhapsody\Test\RhapsodyTestCase;

class QueryTest extends RhapsodyTestCase
{
    public function testWhere()
    {
        $author = Rhapsody::query('Author')->filterByName('')->findOne();
        // var_dump($author);

        Rhapsody::setup(array(
            'dbname' => 'australia_oh',
            'user' => 'root',
            'password' => '123456',
            'host' => 'localhost',
            'driver' => 'pdo_mysql'
        ));

        Rhapsody::setQueryLogger();

        $preshop = Rhapsody::create('preshop');
        $preshop->name = "Cuisine Courier";
        $preshop->address = null;
        $preshop->postcode = 3065;

        $shop = Rhapsody::query('Shop')
            ->filterByName($preshop->name)
            ->filterByAddress($preshop->address)
            ->filterByPostcode($preshop->postcode)
            ->findOne();

        print_r(Rhapsody::getLastExecutedQuery());

    }
}
