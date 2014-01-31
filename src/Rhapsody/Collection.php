<?php

namespace Rhapsody;

use Rhapsody\Utils\Collection as BaseCollection;
use Closure;

class Collection extends BaseCollection
{
    private $table;
    private $virtualColumns = array();

    public static function create($elements = array())
    {
        $collection = new static();
        $collection->fromArray($elements);

        return $collection;
    }

    public function getObjects()
    {
        return $this->getElements();
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

    public function toColumnValues($columns)
    {
        if (! is_array($columns)) {
            $columns = array($columns);
        }

        $result = array();
        foreach ($this->getObjects() as $object) {
            if (1 == count($columns)) {
                $result[] = $object->get($columns[0]);
            } else {
                $row = array();
                foreach ($columns as $column) {
                    $row[$column] = $object->get($column);
                }
                $result[] = $row;
            }
        }

        return $result;
    }

    public function addVirtualColumns(array $columns)
    {
        $this->virtualColumns = array_merge($this->virtualColumns, $columns);
    }

    /**
     * Populate the data from array
     *
     * @param  array $rows
     */
    public function fromArray(array $rows)
    {
        $this->clear();
        $rows = $this->elementsToArray($rows);

        $cache = Rhapsody::getObjectCache();

        foreach ($rows as $row) {
            if (isset($row['id']) && $cache->containsObject($row['id'], $this->table)) {
                $object = $cache->fetchObject($row['id'], $this->table);

                if (! empty($this->virtualColumns) && ! $object->hasVirtualColumns($this->virtualColumns)) {
                    $object->addVirtualColumns($this->virtualColumns);
                    $object->populateVirtualColumns($row);
                }
            } else {
                $object = Rhapsody::create($this->table);
                $object->addVirtualColumns($this->virtualColumns);
                $object->fromArray($row);
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
     * @param  Object   $object
     *
     * @return null|Object  The removed element on success, NULL otherwise
     */
    public function remove($object)
    {
        if (! $object instanceof Object) {
            throw new RhapsodyException("Instance of \Rhapsody\Object expected!");
        }

        return parent::remove($object);
    }

    /**
     * Returns a new collection with objects that are only in this collection and NOT in the given collection
     *
     * @param  Collection $collection
     *
     * @return Collection
     */
    public function diff(BaseCollection $collection)
    {
        if (! $collection instanceof Collection) {
            throw new RhapsodyException("Invalid collection type ".get_class($collection));
        }

        if ($this->getTable() != $collection->getTable()) {
            throw new RhapsodyException("Cannot compare collections of 2 different tables: ".$collection->getTable().' '.$this->getTable());
        }

        // return parent::diff($collection);

        $diff = Rhapsody::createCollection($this->getTable());

        foreach ($this->getObjects() as $object) {
            if (! $collection->contains($object)) {
                $diff->add($object);
            }
        }

        return $diff;
    }
}
