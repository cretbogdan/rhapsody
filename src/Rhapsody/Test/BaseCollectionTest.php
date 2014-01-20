<?php

namespace Rhapsody\Test;

use Rhapsody;
use Rhapsody\Utils\Collection;

class BaseCollectionTest extends RhapsodyTestCase
{
    public function testAddRemove()
    {
        $coll = new Collection(range(1, 3));
        $coll->prepend(0);
        $this->assertEquals(range(0, 3), $coll->getElements());

        $coll->add(4);
        $this->assertEquals(range(0, 4), $coll->getElements());

        $coll->append(5);
        $this->assertEquals(range(0, 5), $coll->getElements());

        $coll->remove(5)->remove(4)->remove(3)->remove(2)->remove(1);
        $this->assertEquals(array(0), $coll->getElements());
    }

    /**
     * @dataProvider rangeCollectionsProvider
     */
    public function testDiff($coll1, $coll2)
    {
        $diff = $coll1->diff($coll2);
        $this->assertEquals(range(6, 10), $diff->getValues());
    }

    /**
     * @dataProvider rangeCollectionsProvider
     */
    public function testFilter($coll1, $coll2)
    {
        $even = function ($number) {return $number % 2 == 0;};
        $evenNumbers = $coll2->filter($even);
        $this->assertEquals(array(2, 4), $evenNumbers->getValues());
    }

    /**
     * @dataProvider peopleCollectionProvider
     */
    public function testFilterByAttribute($people)
    {
        $jane = $people->findByAttribute('name', 'Jane');
        $women = $people->filterByAttribute('sex', 'F');

        $this->assertEquals(3, $women->count());
        $this->assertTrue($women->contains($jane));
    }

    /**
     * @dataProvider peopleCollectionProvider
     */
    public function testFindByAttribute($people)
    {
        $jane = $people->findByAttribute('name', 'Jane');
        $this->assertSame($people->first(), $jane);
    }

    /**
     * @dataProvider rangeCollectionsProvider
     */
    public function testMap($coll1, $coll2)
    {
        $double = function($number){return $number * 2;};

        $doubled = $coll2->map($double);
        $this->assertEquals(array(2, 4, 6, 8, 10), $doubled->getValues());
    }

    /**
     * @dataProvider rangeCollectionsProvider
     */
    public function testReduce($coll1, $coll2)
    {
        $biggestNumber = $coll2->reduce(function($el1, $el2) {
            return $el1 >= $el2 ? $el1 : $el2;
        });

        $this->assertEquals(5, $biggestNumber);
    }

    /**
     * @dataProvider rangeCollectionsProvider
     */
    public function testPartition($coll1, $coll2)
    {
        $even = function ($number) {return $number % 2 == 0;};

        list($evenCol, $oddColl) = $coll2->partition($even);
        $this->assertEquals(array(2, 4), $evenCol->getValues());
        $this->assertEquals(array(1, 3, 5), $oddColl->getValues());
    }

    /**
     * @dataProvider rangeCollectionsProvider
     */
    public function testSlice($coll1, $coll2)
    {
        $sliced = $coll2->slice(3);
        $this->assertEquals(array(4, 5), $sliced->getValues());
    }

    /**
     * @dataProvider rangeCollectionsProvider
     */
    public function testEach($coll1, $coll2)
    {
        $doubleRef = function(&$number){$number = $number * 2;};

        $coll2->each($doubleRef);
        $this->assertEquals(array(2, 4, 6, 8, 10), $coll2->getElements());
    }

    /**
     * @dataProvider rangeCollectionsProvider
     */
    public function testReverse($coll1, $coll2)
    {
        $reversed = $coll2->reverse();
        $this->assertEquals(array(5, 4, 3, 2, 1), $reversed->getElements());
    }

    /**
     * @dataProvider rangeCollectionsProvider
     */
    public function testSumProd($coll1, $coll2)
    {
        $sum = $coll2->sum();
        $this->assertEquals(15, $sum);

        $prod = $coll2->prod();
        $this->assertEquals(120, $prod);
    }

    public function testPad()
    {
        $coll = new Collection();
        $coll->add(array(2, 3));
        $coll->add(array(2, 5));
        $coll->add(array(2, 7));

        $padded = $coll->pad(3, 0);
        $this->assertEquals(array(2, 3, 0), $padded->first());
        $this->assertEquals(array(2, 5, 0), $padded->second());
        $this->assertEquals(array(2, 7, 0), $padded->last());
    }

    /**
     * @dataProvider peopleCollectionProvider
     */
    public function testChunk($people)
    {
        $chunks = $people->chunk(3);
        $women = $people->filterByAttribute('sex', 'F');
        $men = $people->filterByAttribute('sex', 'M');
        $men->resetKeys();

        $this->assertEquals($women, $chunks->first());
        $this->assertEquals($men, $chunks->second());
    }

    /**
     * @dataProvider peopleCollectionProvider
     */
    public function testToAttributeValue($people)
    {
        $names = $people->toAttributeValue('name');
        $actualNames = array('Jane','Mairie','Simone','John','George','Michael');

        $this->assertEquals($actualNames, $names);

        $sexes = $people->toAttributeValue('sex');
        $this->assertEquals(array('F', 'M'), array_values(array_unique($sexes)));
    }

    /**
     * @dataProvider peopleCollectionProvider
     */
    public function testToAttributeValues($people)
    {
        $women = $people->filterByAttribute('sex', 'F');
        $countries = $women->toAttributeValues(array('name','country'));

        $expected = array(
          array('name' => 'Jane', 'country' => 'USA'),
          array('name' => 'Mairie', 'country' => 'UK'),
          array('name' => 'Simone', 'country' => 'USA'),
        );

        $this->assertEquals($expected, $countries);
    }


    public function rangeCollectionsProvider()
    {
        return array(
            array(new Collection(range(1, 10)), new Collection(range(1, 5))),
        );
    }

    public function peopleCollectionProvider()
    {
        $people = array(
            array('name' => 'Jane',     'sex' => 'F', 'country' => 'USA'),
            array('name' => 'Mairie',   'sex' => 'F', 'country' => 'UK'),
            array('name' => 'Simone',   'sex' => 'F', 'country' => 'USA'),
            array('name' => 'John',     'sex' => 'M', 'country' => 'USA'),
            array('name' => 'George',   'sex' => 'M', 'country' => 'South Africa'),
            array('name' => 'Michael',  'sex' => 'M', 'country' => 'Australia'),
        );

        return array(
            array(new Collection($people))
        );
    }
}
