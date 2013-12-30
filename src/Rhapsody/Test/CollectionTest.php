<?php

namespace Rhapsody\Test;

use Rhapsody;

class CollectionTest extends RhapsodyTestCase
{
    public function setUp()
    {
        Rhapsody::query('Author')->truncate();
    }

    public function testDiff()
    {
        $authors = Rhapsody::createCollection('Author');

        $author1 = Rhapsody::create('Author')->setName('Author1');
        $author2 = Rhapsody::create('Author')->setName('Author2');
        $author3 = Rhapsody::create('Author')->setName('Author3');

        $authors->add($author1);
        $authors->add($author2);
        $authors->add($author3);

        $otherAuthors = Rhapsody::createCollection('Author');
        $otherAuthors->add($author2);

        $diff = $authors->diff($otherAuthors);

        $this->assertEquals(2, $diff->count());
        $this->assertTrue($diff->contains($author1));
        $this->assertTrue($diff->contains($author3));
    }
}
