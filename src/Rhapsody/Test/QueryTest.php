<?php

namespace Rhapsody\Test;

use Rhapsody;
use Rhapsody\Test\RhapsodyTestCase;

class QueryTest extends RhapsodyTestCase
{
    public function testWhere()
    {
        $query = Rhapsody::query('Campaign')->where('name = ?', 'a');
    }
}
