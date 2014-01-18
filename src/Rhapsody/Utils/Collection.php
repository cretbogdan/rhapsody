<?php

namespace Rhapsody\Utils;

use Closure;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use ArrayAccess;

class Collection implements Countable, IteratorAggregate, ArrayAccess
{
    protected $elements;

    public function __construct(array $elements = array())
    {
        $this->elements = $elements;
    }

    public function getElements()
    {
        return $this->elements;
    }

    public function getFirst()
    {
        return $this->first();
    }

    public function first()
    {
        return reset($this->elements);
    }

    public function getLast()
    {
        return $this->last();
    }

    public function last()
    {
        return end($this->elements);
    }

    public function key()
    {
        return key($this->elements);
    }

    public function getNext()
    {
        return $this->next();
    }

    public function next()
    {
        return next($this->elements);
    }

    public function getCurrent()
    {
        return $this->current();
    }

    public function current()
    {
        return current($this->elements);
    }

    public function removeByKey($key)
    {
        if (isset($this->elements[$key])) {
            unset($this->elements[$key]);
        }

        return $this;
    }

    public function remove($elements)
    {
        if (is_array($elements)) {
            foreach ($elements as $element) {
                $this->doRemove($element);
            }
        } else {
            $this->doRemove($elements);
        }

        return $this;
    }

    protected function doRemove($element)
    {
        $key = array_search($element, $this->elements, true);

        if ($key !== false) {
            $this->removeByKey($key);
        }
    }

    public function pop()
    {
        return array_pop($this->elements);
    }

    public function removeLast()
    {
        return $this->pop();
    }

    public function shift()
    {
        return array_shift($this->elements);
    }

    public function removeFirst()
    {
        return $this->shift();
    }

    public function offsetExists($offset)
    {
        return $this->containsKey($offset);
    }

    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    public function offsetSet($offset, $value)
    {
        if ( ! isset($offset)) {
            return $this->add($value);
        }
        return $this->set($offset, $value);
    }

    public function offsetUnset($offset)
    {
        return $this->removeByKey($offset);
    }

    public function containsKey($key)
    {
        return isset($this->elements[$key]);
    }

    public function contains($element)
    {
        return in_array($element, $this->elements);
    }

    public function has($element)
    {
        return $this->contains($element);
    }

    public function exists(Closure $callback)
    {
        foreach ($this->elements as $element) {
            if ($callback($element)) {
                return true;
            }
        }

        return false;
    }

    public function find(Closure $callback)
    {
        foreach ($this->elements as $element) {
            if ($callback($element)) {
                return $element;
            }
        }

        return null;
    }

    public function findByAttribute($name, $value)
    {
        $callback = function ($element) use ($name, $value) {
            return $element[$name] == $value;
        };

        return $this->find($callback);
    }

    public function indexOf($element)
    {
        return array_search($element, $this->elements, true);
    }

    public function get($key)
    {
        if (isset($this->elements[$key])) {
            return $this->elements[$key];
        }
        return null;
    }

    public function getKeys()
    {
        return array_keys($this->elements);
    }

    public function resetKeys()
    {
        $this->elements = array_values($this->elements);

        return $this;
    }

    public function getValues()
    {
        return array_values($this->elements);
    }

    public function count()
    {
        return count($this->elements);
    }

    public function set($key, $value)
    {
        $this->elements[$key] = $value;

        return $this;
    }

    public function add($value)
    {
        $this->elements[] = $value;

        return $this;
    }

    public function append($value)
    {
        return $this->add($value);
    }

    public function prepend($value)
    {
        array_unshift($this->elements, $value);

        return $this;
    }

    public function isEmpty()
    {
        return empty($this->elements);
    }

    public function getIterator()
    {
        return new ArrayIterator($this->elements);
    }

    public function slice($offset, $length = null)
    {
        return new static(array_slice($this->elements, $offset, $length, true));
    }

    public function map(Closure $callback)
    {
        return new static(array_map($callback, $this->elements));
    }

    public function reverse()
    {
        return new static(array_reverse($this->elements));
    }

    public function filter(Closure $callback)
    {
        return new static(array_filter($this->elements, $callback));
    }

    public function each(Closure $callback)
    {
        return $this->walk($callback);
    }

    public function walk(Closure $callback)
    {
        array_walk($this->elements, $callback);

        return $this;
    }

    public function partition(Closure $callback)
    {
        $coll1 = array();
        $coll2 = array();

        foreach ($this->elements as $key => $element) {
            if ($callback($element)) {
                $coll1[$key] = $element;
            } else {
                $coll2[$key] = $element;
            }
        }
        return array(new static($coll1), new static($coll2));
    }

    public function diff(Collection $collection)
    {
        $callback = function ($element) use ($collection) {
            return ! $collection->contains($element);
        };

        return $this->filter($callback);
    }

    public function reduce(Closure $callback)
    {
        return array_reduce($this->elements, $callback);
    }

    public function clone()
    {
        $clone = clone $this;

        return $clone;
    }

    public function shuffle()
    {
        shuffle($this->elements);

        return $this;
    }

    public function clear()
    {
        $this->elements = array();

        return $this;
    }

    public function __toString()
    {
        return __CLASS__ . '@' . spl_object_hash($this);
    }

    public function __call($name, $arguments)
    {
        $function = 'array_'.$name;

        if (function_exists($function)) {
            array_unshift($arguments, $this->elements);
            $result = call_user_func_array($function, $arguments);

            return is_array($result) ? new static($result) : $result;
        }

        throw new \BadMethodCallException("Method Rhapsody\Utils\Collection::$name does not exist!");
    }
}

