<?php

namespace Rhapsody\Test;

use Rhapsody;

class ObjectTest extends RhapsodyTestCase
{
    public function setUp()
    {
        Rhapsody::query('Author')->truncate();
    }

    public function testCreation()
    {
        $conn = Rhapsody::getConnection();
        $author = Rhapsody::create('Author');

        $this->assertNotNull($author);
        $this->assertInstanceOf('\Rhapsody\Object', $author);

        $this->assertNull($author->name);
        $author->name = 'Aristotel';
        $this->assertEquals('Aristotel', $author->name);

        $this->assertNull($author->id);
        $author->save();

        $this->assertNotNull($author->id);
        $this->assertTrue(is_int($author->id));

        $logger = $conn->getConfiguration()->getSQLLogger();
        $queryTotal = $logger->currentQuery;

        $author->save();
        $this->assertEquals($queryTotal, $logger->currentQuery);

        $author->save();
        $this->assertEquals($queryTotal, $logger->currentQuery);

        $author->save();
        $this->assertEquals($queryTotal, $logger->currentQuery);
        $this->assertFalse($author->isModified());

        $author->name = 'Plato';
        $this->assertTrue($author->isModified());
        $author->save();
        $this->assertFalse($author->isModified());

        $author = Rhapsody::query('Author')->filterByName('Plato')->findOne();
        $this->assertFalse($author->isNew());
        $this->assertFalse($author->isModified());

        $author->name = 'Heraclitus';
        $this->assertTrue($author->isModified());

        $author->save();
        $this->assertFalse($author->isModified());
        $this->assertFalse($author->isNew());

        $authors = Rhapsody::query('Author')->find();

        foreach ($authors as $author) {
            $this->assertFalse($author->isNew());
            $this->assertFalse($author->isModified());
        }
    }
}
