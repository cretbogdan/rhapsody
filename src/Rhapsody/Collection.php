<?php

namespace Rhapsody;

use Doctrine\Common\Collections\ArrayCollection as BaseCollection;

class Collection extends BaseCollection
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
        foreach ($this->toArray() as $object) {
            $object->save();
        }
        Rhapsody::getConnection()->commit();
    }


    /**
     * Populate the data from array
     *
     * @param  array $rows
     */
    public function fromArray(array $rows, $isNew = true)
    {
        $this->clear();

        foreach ($rows as $row) {
            $this->add(Rhapsody::create($this->table, $row, $isNew));
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

    public function remove($key)
    {
        if ($key instanceof Object) {
            return $this->removeElement($key);
        }

        return parent::remove($key);
    }
}
