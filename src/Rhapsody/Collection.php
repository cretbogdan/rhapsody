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

    public function containsObjectId($object)
    {
        $id = $object instanceof Object ? $object->get('id') : $object;

        return in_array($id, $this->toColumnValues('id'));
    }

    public function findById($object)
    {
        if (! $this->containsObjectId($object)) {
            return null;
        }

        $id = $object instanceof Object ? $object->get('id') : $object;

        foreach ($this->getObjects() as $collectionObject) {
            if ($id == $collectionObject->id) {
                return $collectionObject;
            }
        }
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

    public function remove($key)
    {
        if ($key instanceof Object) {
            return $this->removeElement($this->findById($key));
        }

        return parent::remove($key);
    }
}
