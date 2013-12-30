<?php

namespace Rhapsody;

use Doctrine\Common\Collections\ArrayCollection;

class Collection extends ArrayCollection
{
    private $table;

    public function __construct($table = null, array $elements = array())
    {
        $this->table = $table;
        parent::__construct($elements);
    }

    public static function create($table, array $elements = array(), $isNew = true)
    {
        $collection = new static($table);
        $collection->fromArray($elements, $isNew);

        return $collection;
    }

    /**
     * Save all objects to database
     */
    public function save()
    {
        Rhapsody::getConnection()->beginTransaction();
        foreach ($this->getObjects() as $object) {
            $object->save();
        }
        Rhapsody::getConnection()->commit();
    }

    /**
     * Delete all objects from database
     */
    public function delete()
    {
        Rhapsody::getConnection()->beginTransaction();
        foreach ($this->getObjects() as $object) {
            $object->delete();
        }
        Rhapsody::getConnection()->commit();
    }

    public function toColumnValues($column)
    {
        if (! Rhapsody::getTableManager()->hasColumn($this->getTable(), $column)) {
            throw new \InvalidArgumentException("Table '$this->table' does not have column '$column'!");
        }

        $result = array();

        foreach ($this->getObjects() as $object) {
            $result[] = $object->get($column);
        }

        return $result;
    }

    /**
     * Populate the data from array
     *
     * @param  array $rows
     */
    public function fromArray(array $rows, $isNew = true)
    {
        $this->clear();
        $cache = Rhapsody::getObjectCache();

        foreach ($rows as $row) {
            if (isset($row['id']) && $cache->containsObject($row['id'], $this->table)) {
                $object = $cache->fetchObject($row['id'], $this->table);
            } else {
                $object = Rhapsody::create($this->table, $row, $isNew);
                $cache->saveObject($object);
            }

            $this->add($object);
        }
    }

    /**
     * Set the table of the elements in the collection
     *
     * @param string $table Name of the Propel object classes stored in the collection
     */
    public function setTable($table)
    {
        $this->table = $table;
    }

    /**
     * Get the table of the elements in the collection
     *
     * @return string Name of the Propel object class stored in the collection
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * Remove an element from the collection
     *
     * @param  int|Object   $key
     *
     * @return null|Object  The removed element on success, NULL otherwise
     */
    public function remove($key)
    {
        if ($key instanceof Object) {
            return $this->removeElement($key);
        }

        return parent::remove($key);
    }

    /**
     * Returns an array represantation of the collection
     *
     * @return array
     */
    public function toArray()
    {
        $result = array();

        foreach ($this->getObjects() as $object) {
            $result[] = $object->toArray();
        }

        return $result;
    }

    /**
     * Returns a new collection with objects that are only in this collection and NOT in the given collection
     *
     * @param  Collection $collection
     *
     * @return Collection
     */
    public function diff(Collection $collection)
    {
        if ($this->getTable() != $collection->getTable()) {
            throw new RhapsodyException("Cannot compare collections of 2 different tables: ".$collection->getTable().' '.$this->getTable());
        }

        $diff = Rhapsody::createCollection($this->getTable());

        foreach ($this->getObjects() as $object) {
            if (! $collection->contains($object)) {
                $diff->add($object);
            }
        }

        return $diff;
    }
}
