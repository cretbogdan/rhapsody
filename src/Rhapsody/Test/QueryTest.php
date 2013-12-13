<?php

namespace Rhapsody\Test;

use Rhapsody;
use Rhapsody\Test\RhapsodyTestCase;

class QueryTest extends RhapsodyTestCase
{
    public function setUp()
    {
        Rhapsody::query('Author')->truncate();
        Rhapsody::query('Book')->truncate();
    }

    public function testWhere()
    {
        Rhapsody::create('Author')->setName('Aristotel')->save();
        $this->assertEquals(1, Rhapsody::query('Author')->filterByName('Aristotel')->count());

        $aristotel = Rhapsody::query('Author')->filterByName('Aristotel')->findOne();
        $this->assertNotNull($aristotel);

        $author = Rhapsody::query('Author')->filterByName('Plato')->findOne();
        $this->assertNull($author);

        $book = Rhapsody::create('Book')->setAuthorId($aristotel->id)->setName('Rhetoric')->save();

        $author = $book->author;
        $this->assertNotNull($author);
        $this->assertEquals($author->id, $aristotel->id);




        // $books = $aristotel->books;
        // echo Rhapsody::getTotalQueries();

        // $author = Rhapsody::query('Author')->filterByName('')->findOne();

    }
}
