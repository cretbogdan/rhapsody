<?php

namespace Rhapsody\Test;

use Rhapsody;
use Rhapsody\Utils\Collection;

class BaseCollectionTest extends RhapsodyTestCase
{
    public function testCollection()
    {
        $double = function($number){return $number * 2;};
        $doubleRef = function(&$number){$number = $number * 2;};
        $even = function ($number) {return $number % 2 == 0;};

        $coll1 = new Collection(range(1, 10));
        $coll2 = new Collection(range(1, 5));

        $diff = $coll1->diff($coll2);
        $this->assertEquals(range(6, 10), $diff->getValues());

        $evenNumbers = $coll2->filter($even);
        $this->assertEquals(array(2, 4), $evenNumbers->getValues());

        $doubled = $coll2->map($double);
        $this->assertEquals(array(2, 4, 6, 8, 10), $doubled->getValues());

        $biggestNumber = $coll2->reduce(function($el1, $el2){return $el1 >= $el2 ? $el1 : $el2;});
        $this->assertEquals(5, $biggestNumber);

        $sliced = $coll2->slice(3);
        $this->assertEquals(array(4, 5), $sliced->getValues());

        list($evenCol, $oddColl) = $coll2->partition($even);
        $this->assertEquals(array(2, 4), $evenCol->getValues());
        $this->assertEquals(array(1, 3, 5), $oddColl->getValues());

        $reversed = $coll2->reverse();
        $this->assertEquals(array(5, 4, 3, 2, 1), $reversed->getElements());

        $coll2->each($doubleRef);
        $this->assertEquals(array(2, 4, 6, 8, 10), $coll2->getElements());

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
}
