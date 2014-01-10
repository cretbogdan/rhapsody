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
    }

    public function testNull()
    {
        $author = Rhapsody::query('Author')->filterByName(null)->findOne();
        $this->assertNull($author);
    }

    public function testNotNull()
    {
        $authors = Rhapsody::query('Author')->filterByName(null, 'not null')->find();
        $this->assertContains('SELECT author.* FROM author author WHERE name  IS NOT NULL', Rhapsody::getLastExecutedQuery()['sql']);
    }

    public function testVirtualColumns()
    {
        Rhapsody::create('Author')->setName('Aristotel')->save();
        $authors = Rhapsody::query('Author')->withColumn('UPPER(name)', 'upper_name')->find();
        $author = $authors->first();

        $this->assertTrue($author->hasVirtualColumn('upper_name'));
        $this->assertEquals('ARISTOTEL', $author->upperName);
    }

    public function testVirtualColumn()
    {
        Rhapsody::create('Author')->setName('Aristotel')->save();
        $author = Rhapsody::query('Author')->withColumn('UPPER(name)', 'upper_name')->findOne();

        $this->assertTrue($author->hasVirtualColumn('upper_name'));
        $this->assertEquals('ARISTOTEL', $author->upperName);

        Rhapsody::query('Author')->withColumn('UPPER(name)', 'upper_name')->groupById()->orderBy('upper_name')->find();
    }

    public function testJoin()
    {
        $author = Rhapsody::create('Author')->setName('Aristotel')->save();
        $book = Rhapsody::create('Book')->setName('Rhetoric')->setAuthor($author)->save();

        $plato = Rhapsody::create('Author')->setName('Plato')->save();
        $republic = Rhapsody::create('Book')->setName('Republic')->setAuthor($plato)->save();

        $books = Rhapsody::query('Book')->innerJoin('Author', 'a')->where('a.name = ?', 'Aristotel')->find();
        $this->assertEquals(1, $books->count());

        $books = Rhapsody::query('Book')->innerJoin('Author', 'a')->where('a.name = ?', 'NotAristotel')->find();
        $this->assertEquals(0, $books->count());

        $books = Rhapsody::query('Book')->leftJoin('Author', 'a')->where('a.name <> ?', 'Aristotel')->find();
        $this->assertEquals(1, $books->count());
        $this->assertEquals('Republic', $books->first()->name);
        $this->assertSame($republic, $books->getLast());
    }
}
