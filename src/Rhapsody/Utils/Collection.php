<?php

namespace Rhapsody\Utils;

use Closure;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use ArrayAccess;

class Collection implements Countable, IteratorAggregate, ArrayAccess
{
    protected $metas = [];
    protected $elements;

    public function __construct($elements = array())
    {
        $this->setElements($elements);
    }

    public static function create($elements = array())
    {
        return new static($elements);
    }

    public function setElements(array $elements)
    {
        $this->elements = $this->elementsToArray($elements);
    }

    public function getElements()
    {
        return $this->elements;
    }

    public function setMeta($name, $value)
    {
        $this->metas[$name] = $value;

        return $this;
    }

    public function getMeta($name, $default = null)
    {
        return isset($this->metas[$name]) ? $this->metas[$name] : $default;
    }

    public function getFirst()
    {
        return $this->first();
    }

    public function first()
    {
        return reset($this->elements);
    }

    public function getSecond()
    {
        return $this->second();
    }

    public function second()
    {
        return $this->get(1);
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

    public function copy()
    {
        $clone = clone $this;

        return $clone;
    }

    public function shuffle()
    {
        shuffle($this->elements);

        return $this;
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

    public function keyExists($key)
    {
        return array_key_exists($key, $this->elements);
    }

    public function hasKey($key)
    {
        return $this->containsKey($key);
    }

    public function containsKey($key)
    {
        return isset($this->elements[$key]);
    }

    public function has($element)
    {
        return $this->contains($element);
    }

    public function contains($element)
    {
        return in_array($element, $this->elements);
    }

    public function hasAll($elements)
    {
        return $this->containsAll($elements);
    }

    public function containsAll($elements)
    {
        if ($elements instanceof Collection) {
            $elements = $elements->getElements();
        } elseif (! is_array($elements)) {
            throw new \InvalidArgumentException("Expected instance of Collection or array!");
        }

        foreach ($elements as $element) {
            if (! $this->contains($element)) {
                return false;
            }
        }

        return true;
    }

    public function exists(Closure $callback)
    {
        foreach ($this->elements as $key => $element) {
            if ($callback($element, $key)) {
                return true;
            }
        }

        return false;
    }

    public function find(Closure $callback)
    {
        foreach ($this->elements as $key => $element) {
            if ($callback($element, $key)) {
                return $element;
            }
        }

        return null;
    }

    public function findBy($attribute, $value)
    {
        return $this->findByAttribute($attribute, $value);
    }

    public function findByAttribute($attribute, $value)
    {
        $callback = $this->getAttributeFilterClosure($attribute, $value);

        return $this->find($callback);
    }

    public function indexOf($element)
    {
        return $this->search($element);
    }

    public function search($element)
    {
        return array_search($element, $this->elements, true);
    }

    public function get($key, $default = null)
    {
        if (isset($this->elements[$key])) {
            return $this->elements[$key];
        }

        return $default;
    }

    public function getKeys()
    {
        return array_keys($this->elements);
    }

    public function getValues()
    {
        return array_values($this->elements);
    }

    public function max($attribute = null)
    {
        $values = $attribute ? $this->toValue($attribute) : $this->elements;

        return max($values);
    }

    public function min($attribute = null)
    {
        $values = $attribute ? $this->toValue($attribute) : $this->elements;

        return min($values);
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

    public function setAttribute($attribute, $value)
    {
        foreach ($this->elements as $element) {
            static::setElementValue($attribute, $value, $element);
        }

        return $this;
    }


    public function push($value)
    {
        foreach (func_get_args() as $value) {
            $this->add($value);
        }

        return $this;
    }

    public function addAll($values)
    {
        foreach ($values as $value) {
            $this->add($value);
        }

        return $this;
    }


    public function addUniques($values)
    {
        $values = $this->elementsToArray($values);

        foreach ($values as $value) {
            $this->addUnique($value);
        }

        return $this;
    }

    public function addUnique($value)
    {
        if (! $this->contains($value)) {
            $this->add($value);
        }

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

    public function unique()
    {
        return new static(array_unique($this->elements));
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

    public function filterByAttribute($name, $value)
    {
        $callback = $this->getAttributeFilterClosure($name, $value);

        return $this->filter($callback);
    }

    public function filterBy($name, $value)
    {
        return $this->filterByAttribute($name, $value);
    }

    public function diff(Collection $collection)
    {
        $callback = function ($element) use ($collection) {
            return ! $collection->contains($element);
        };

        return $this->filter($callback);
    }

    public function toKeyValue($keyAttribute, $valueAttribute)
    {
        $result = [];

        foreach ($this->elements as $element) {
            $key = static::getElementValue($element, $keyAttribute);
            $value = static::getElementValue($element, $valueAttribute);

            $result[$key] = $value;
        }

        return $result;
    }

    public function toValue($attribute)
    {
        return $this->toAttributeValue($attribute);
    }

    /**
     * Return array with all the attribute values for each element
     *
     * @param  string $attribute
     *
     * @return array
     */
    public function toAttributeValue($attribute)
    {
        $result = array();

        foreach ($this->elements as $element) {
            $result[] = static::getElementValue($element, $attribute);
        }

        return $result;
    }

    public function toValues(array $attributes)
    {
        return $this->toAttributeValues($attributes);
    }

    public function toAttributeValues(array $attributes)
    {
        $result = array();
        foreach ($this->elements as $element) {
             $row = array();
             foreach ($attributes as $attribute) {
                $row[$attribute] = static::getElementValue($element, $attribute);
             }

             $result[] = $row;
        }

        return $result;
    }

    public function join($glue, $attribute = null)
    {
        return $this->implode($glue, $attribute);
    }

    public function implode($glue, $attribute = null)
    {
        $elements = $attribute ? $this->toValue($attribute) : $this->elements;

        return implode($glue, $elements);
    }

    public function sum($attribute = null)
    {
        $toSum = $attribute ? $this->toAttributeValue($attribute) : $this->elements;

        return array_sum($toSum);
    }

    public function product($attribute = null)
    {
        return $this->prod($attribute);
    }

    public function prod($attribute = null)
    {
        $toProd = $attribute ? $this->toAttributeValue($attribute) : $this->elements;

        return array_product($toProd);
    }

    public function pad($size, $value)
    {
        $padded = new static();

        foreach ($this->elements as $element) {
            $padded->add(array_pad($element, $size, $value));
        }

        return $padded;
    }

    public function random($num = 1)
    {
        return $this->rand($num);
    }

    public function rand($num = 1)
    {
        $random = array_rand($this->elements, $num);
        if (1 === $num) {
            $result = $this->get($random);;
        } else {
            $elements = array();
            foreach ($random as $key) {
                $elements[] = $this->get($key);
            }

            $result = new static($elements);
        }

        return $result;
    }

    /**
     * Chunk into several collections
     *
     * @param  int     $size
     * @param  boolean $preserveKeys
     *
     * @return Collection
     */
    public function chunk($size, $preserveKeys = false)
    {
        $chunks = array_chunk($this->elements, $size, $preserveKeys);
        $collections = new static();

        foreach ($chunks as $chunk) {
            $collections->add(new static($chunk));
        }

        return $collections;
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

    public function reduce(Closure $callback)
    {
        return array_reduce($this->elements, $callback);
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

    public function merge($elements)
    {
        $elements = $this->elementsToArray($elements);

        return new static(array_merge($this->elements, $elements));
    }

    public function order(Closure $callback)
    {
        return $this->sort($callback);
    }

    public function sort(Closure $callback)
    {
        uasort($this->elements, $callback);

        return $this;
    }

    public function orderBy($attribute = null, $type = 'asc')
    {
        return $this->sortByAttribute($attribute, $type);
    }

    public function orderByAttribute($attribute = null, $type = 'asc')
    {
        return $this->sortByAttribute($attribute, $type);
    }

    public function sortBy($attribute = null, $type = 'asc')
    {
        return $this->sortByAttribute($attribute, $type);
    }

    public function sortByAttribute($attribute = null, $type = 'asc')
    {
        $type = strtolower($type);
        if(! in_array($type, array('asc', 'desc'))) {
            throw new \InvalidArgumentException("Unknown sort type \"$type\". Valid types: asc, desc!");
        }

        if ($attribute) {
            $callback = function ($element1, $element2) use ($attribute, $type) {
                $val1 = static::getElementValue($element1, $attribute);
                $val2 = static::getElementValue($element2, $attribute);

                return 'asc' == $type ? strcmp($val1, $val2) : strcmp($val2, $val1);
            };

            $this->sort($callback);
        } else {
            'asc' == $type ? asort($this->elements) : arsort($this->elements);

            return $this;
        }
    }

    public function replace($elements)
    {
        $elements = $this->elementsToArray($elements);
        $this->elements = array_replace($this->elements, $elements);

        return $this;
    }

    public function clear()
    {
        $this->elements = array();

        return $this;
    }

    public function resetKeys()
    {
        $this->elements = array_values($this->elements);

        return $this;
    }

    public function rewind()
    {
        return $this->reset();
    }

    public function reset()
    {
        reset($this->elements);

        return $this;
    }

    protected function elementsToArray($elements)
    {
        if ($elements instanceof Collection) {
            $elements = $elements->getElements();
        } elseif (! is_array($elements)) {
            throw new \InvalidArgumentException("Can only merge with array or instance of Rhapsody\Utils\Collection!");
        }

        return $elements;
    }

    public function getAttributeFilterClosure($attribute, $value)
    {
        $callback = function ($element) use ($attribute, $value) {
            $elementValue = static::getElementValue($element, $attribute);

            return $elementValue == $value;
        };

        return $callback;
    }

    public static function getElementValue($element, $attribute)
    {
        $valid = false;
        $value = null;

        if (is_array($element)) {
            $valid = true;
            $value = $element[$attribute];
        } elseif (is_object($element)) {
            $method = 'get'.ucfirst($attribute);

            if (is_callable(array($element, $method))) {
                $valid = true;
                $value = $element->$method();
            } elseif (is_callable(array($element, $attribute))) {
                $valid = true;
                $value = $element->$attribute();
            } elseif (method_exists($element, 'get')) {
                $valid = true;
                $value = $element->get($attribute);
            }
        }

        if (! $valid) {
            throw new \InvalidArgumentException("Cannot get value \"$attribute\" for element!");
        }

        return $value;
    }

    public static function setElementValue($attribute, $value, $element)
    {
        if (is_array($element)) {
            $valid = true;
            $element[$attribute] = $value;;
        } elseif (is_object($element)) {
            $method = 'set'.ucfirst($attribute);

            if (is_callable(array($element, $method))) {
                $valid = true;
                $element->$method($value);
            } elseif (is_callable(array($element, $attribute))) {
                $valid = true;
                $element->$attribute($value);
            } elseif (method_exists($element, 'set')) {
                $valid = true;
                $element->set($attribute, $value);
            }
        }

        if (! $valid) {
            throw new \InvalidArgumentException("Cannot get value \"$attribute\" for element!");
        }
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
