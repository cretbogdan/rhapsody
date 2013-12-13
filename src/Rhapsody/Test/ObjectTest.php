<?php

namespace Rhapsody\Test;

use Rhapsody;

class ObjectTest extends RhapsodyTestCase
{
    public function setUp()
    {
        Rhapsody::query('Author')->truncate();
        Rhapsody::query('Book')->truncate();
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

        $totalQueries = Rhapsody::getTotalQueries();

        $author->save();
        $this->assertEquals($totalQueries, Rhapsody::getTotalQueries());

        $author->save();
        $this->assertEquals($totalQueries, Rhapsody::getTotalQueries());

        $author->save();
        $this->assertEquals($totalQueries, Rhapsody::getTotalQueries());
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


    public function testParent()
    {
        $author = Rhapsody::create('Author')->setName('Aristotel');
        $author->save();
        $book = Rhapsody::create('Book')->setName('Rhetoric')->setAuthor($author)->save();
        $this->assertEquals($book->authorId, $author->id);

        $author = Rhapsody::create('Author')->setName('Plato');
        $book = Rhapsody::create('Book')->setName('Republic')->setAuthor($author)->save();
        $this->assertEquals($book->authorId, $author->id);
    }


    public function testChildren()
    {
        $author = Rhapsody::create('Author')->setName('Aristotel')->save();
        $book = Rhapsody::create('Book')->setName('Rhetoric')->setAuthor($author)->save();

        $books = $author->books;
        $this->assertEquals(1, $books->count());
        $this->assertEquals($book->id, $books->first()->id);

        $otherAuthor = Rhapsody::create('Author')->setName('Montaigne')->save();
        $otherAuthor->setBooks($books);
        $this->assertEquals(0, $books->count());
        $this->assertEquals(1, $otherAuthor->books->count());
    }
}
