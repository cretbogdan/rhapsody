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
        $author = Rhapsody::create('Author')->setName('Aristotel')->save();
        $book = Rhapsody::create('Book')->setName('Rhetoric')->setAuthor($author)->save();
        $this->assertEquals($book->authorId, $author->id);

        $author = Rhapsody::create('Author')->setName('Plato');
        $this->assertTrue($author->isModified());
        $book = Rhapsody::create('Book')->setName('Republic')->setAuthor($author)->save();
        $this->assertEquals($book->authorId, $author->id);
        $this->assertFalse($author->isModified());
        $this->assertNotNull($book->author);
    }

    public function testObjectCache()
    {
        $cache = Rhapsody::getObjectCache();

        $author = Rhapsody::create('Author')->setName('Aristotel')->save();
        $this->assertTrue($cache->containsObject(1, 'author'));

        $book = Rhapsody::create('Book')->setName('Rhetoric')->setAuthorId($author->id)->save();
        $this->assertTrue($cache->containsObject(1, 'book'));
        $this->assertSame($author, $book->author);
        $this->assertSame($author->books->first(), $book);
    }

    public function testChildren()
    {
        $author = Rhapsody::create('Author')->setName('Aristotel')->save();
        $book = Rhapsody::create('Book')->setName('Rhetoric')->setAuthor($author)->save();

        $books = $author->books;
        $this->assertEquals(1, $books->count());
        $this->assertEquals($book->id, $books->first()->id);

        $montaigne = Rhapsody::create('Author')->setName('Montaigne')->save();
        $montaigne->setBooks($books);
        $this->assertEquals(0, $books->count());
        $this->assertEquals(1, $montaigne->books->count());
        $this->assertFalse($books == $montaigne->books);
    }

    public function testChildrenRemoveAdd()
    {
        $author = Rhapsody::create('Author')->setName('Aristotel')->save();
        $book = Rhapsody::create('Book')->setName('Unknown')->setAuthor($author)->save();

        $this->assertEquals(1, $author->books->count());
        $poetics = Rhapsody::create('Book')->setName('Poetics');

        $author->addBook($poetics);
        $this->assertEquals(2, $author->books->count());

        $author->removeBook($poetics);
        $this->assertEquals(1, $author->books->count());

        $author->removeBook($book);
        $this->assertEquals(0, $author->books->count());

        $author->save();
        $this->assertEquals(0, $author->books->count());
    }

    public function testChildrenAddRemove()
    {
        $author = Rhapsody::create('Author')->setName('Aristotel')->save();
        $montaigne = Rhapsody::create('Author')->setName('Montaigne')->save();

        $book = Rhapsody::create('Book')->setName('Rhetoric')->setAuthor($author)->save();
        $unknownBook = Rhapsody::create('Book')->setName('Unknown')->setAuthor($author)->save();

        $montaigne->addBook($unknownBook);
        $this->assertEquals(1, $author->books->count());
        $this->assertEquals(1, $montaigne->books->count());

        $author->save();
        $this->assertEquals(1, $author->books->count());
        $this->assertEquals(1, $montaigne->books->count());

        $montaigne->save();
        $this->assertEquals(1, $author->books->count());
        $this->assertEquals(1, $montaigne->books->count());

        $author->removeBook($book);
        $this->assertEquals(0, $author->books->count());
        $this->assertNull($book->authorId);
        $this->assertNull($book->author);
    }

    public function testCrossReferencedObjects()
    {

    }
}
