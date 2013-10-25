<?php

namespace Rhapsody\Base;

class Collection extends \ArrayObject
{
    protected $iterator;

    /**
     * Get the data in the collection
     *
     * @return array
     */
    public function getData()
    {
        return $this->getArrayCopy();
    }

    /**
     * Set the data in the collection
     *
     * @param array $data
     */
    public function setData($data)
    {
        $this->exchangeArray($data);
    }

    /**
     * Gets the position of the internal pointer
     *
     * @return integer
     */
    public function getPosition()
    {
        return (int) $this->getInternalIterator()->key();
    }

    /**
     * Move the internal pointer to the beginning of the list
     * And get the first element in the collection
     *
     * @return mixed
     */
    public function getFirst()
    {
        $this->getInternalIterator()->rewind();

        return $this->getCurrent();
    }

    /**
     * Check whether the internal pointer is at the beginning of the list
     *
     * @return boolean
     */
    public function isFirst()
    {
        return $this->getPosition() == 0;
    }

    /**
     * Move the internal pointer backward
     * And get the previous element in the collection
     *
     * @return mixed
     */
    public function getPrevious()
    {
        $pos = $this->getPosition();
        if ($pos == 0) {
            return null;
        } else {
            $this->getInternalIterator()->seek($pos - 1);

            return $this->getCurrent();
        }
    }

    /**
     * Get the current element in the collection
     *
     * @return mixed
     */
    public function getCurrent()
    {
        return $this->getInternalIterator()->current();
    }

    /**
     * Move the internal pointer forward
     * And get the next element in the collection
     *
     * @return mixed
     */
    public function getNext()
    {
        $this->getInternalIterator()->next();

        return $this->getCurrent();
    }

    /**
     * Move the internal pointer to the end of the list
     * And get the last element in the collection
     *
     * @return mixed
     */
    public function getLast()
    {
        $count = $this->count();
        if ($count == 0) {
            return null;
        } else {
            $this->getInternalIterator()->seek($count - 1);

            return $this->getCurrent();
        }
    }

    /**
     * Check whether the internal pointer is at the end of the list
     *
     * @return boolean
     */
    public function isLast()
    {
        $count = $this->count();
        if ($count == 0) {
            // empty list... so yes, this is the last
            return true;
        } else {
            return $this->getPosition() == $count - 1;
        }
    }

    /**
     * Check if the collection is empty
     *
     * @return boolean
     */
    public function isEmpty()
    {
        return $this->count() == 0;
    }

    /**
     * Check if the current index is an odd integer
     *
     * @return boolean
     */
    public function isOdd()
    {
        return (boolean) ($this->getInternalIterator()->key() % 2);
    }

    /**
     * Check if the current index is an even integer
     *
     * @return boolean
     */
    public function isEven()
    {
        return !$this->isOdd();
    }

    /**
     * Get an element from its key
     * Alias for ArrayObject::offsetGet()
     *
     * @param  mixed $key
     * @return mixed The element
     *
     * @throws \Exception
     */
    public function get($key)
    {
        if (! $this->offsetExists($key)) {
            throw new \Exception('Unknown key ' . $key);
        }

        return $this->offsetGet($key);
    }

    /**
     * Pops an element off the end of the collection
     *
     * @return mixed The popped element
     */
    public function pop()
    {
        if ($this->count() == 0) {
            return null;
        }
        $ret = $this->getLast();
        $lastKey = $this->getInternalIterator()->key();
        $this->offsetUnset((string) $lastKey);

        return $ret;
    }

    /**
     * Pops an element off the beginning of the collection
     *
     * @return mixed The popped element
     */
    public function shift()
    {
        // the reindexing is complicated to deal with through the iterator
        // so let's use the simple solution
        $arr = $this->getArrayCopy();
        $ret = array_shift($arr);
        $this->exchangeArray($arr);

        return $ret;
    }

    /**
     * Prepend one or more elements to the beginning of the collection
     *
     * @param  mixed   $value the element to prepend
     * @return integer The number of new elements in the array
     */
    public function prepend($value)
    {
        // the reindexing is complicated to deal with through the iterator
        // so let's use the simple solution
        $arr = $this->getArrayCopy();
        $ret = array_unshift($arr, $value);
        $this->exchangeArray($arr);

        return $ret;
    }

    public function add($value)
    {
        return $this->append($value);
    }

    /**
     * Add an element to the collection with the given key
     * Alias for ArrayObject::offsetSet()
     *
     * @param mixed $key
     * @param mixed $value
     */
    public function set($key, $value)
    {
        $this->offsetSet($key, $value);
    }

    /**
     * Removes a specified collection element
     * Alias for ArrayObject::offsetUnset()
     *
     * @param  mixed $key
     * @return mixed The removed element
     *
     * @\
     */
    public function remove($key)
    {
        if (!$this->offsetExists($key)) {
            throw new \Exception('Unknown key ' . $key);
        }

        return $this->offsetUnset($key);
    }

    /**
     * Clears the collection
     *
     * @return array The previous collection
     */
    public function clear()
    {
        return $this->exchangeArray(array());
    }

    /**
     * Whether or not this collection contains a specified element
     *
     * @param  mixed   $element
     * @return boolean
     */
    public function contains($element)
    {
        return in_array($element, $this->getArrayCopy(), true);
    }

    /**
     * Search an element in the collection
     *
     * @param  mixed $element
     * @return mixed Returns the key for the element if it is found in the collection, FALSE otherwise
     */
    public function search($element)
    {
        return array_search($element, $this->getArrayCopy(), true);
    }

    // IteratorAggregate method

    /**
     * Overrides ArrayObject::getIterator() to save the iterator object
     * for internal use e.g. getNext(), isOdd(), etc.
     *
     * @return ArrayIterator
     */
    public function getIterator()
    {
        $this->iterator = new \ArrayIterator($this);

        return $this->iterator;
    }

    /**
     * @return ArrayIterator
     */
    public function getInternalIterator()
    {
        if (null === $this->iterator) {
            return $this->getIterator();
        }

        return $this->iterator;
    }

    /**
     * Clear the internal Iterator.
     * PHP 5.3 doesn't know how to free a Collection object if it has an attached
     * Iterator, so this must be done manually to avoid memory leaks.
     * @see http://www.propelorm.org/ticket/1232
     */
    public function clearIterator()
    {
        $this->iterator = null;
    }
}
