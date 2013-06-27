<?php

namespace Rhapsody;

use Rhapsody\Base\Collection as BaseCollection;

class Collection extends BaseCollection
{
    private $table;

    public function __construct($table = null, array $data = array())
    {
        $this->table = $table;
        parent::__construct($data);
    }

    public static function create($table, array $rows = array())
    {
        $collection = new static($table);
        $collection->fromArray($rows);

        return $collection;
    }

    /**
     * Save all objects to database
     */
    public function save()
    {
        Rhapsody::getConnection()->beginTransaction();
        foreach ($this->getData() as $object) {
            $object->save();
        }
        Rhapsody::getConnection()->commit();
    }


    /**
     * Populate the data from array
     *
     * @param  array $rows
     */
    public function fromArray(array $rows)
    {
        $class = Rhapsody::getObjectClass($this->table);
        $objects = array();

        foreach ($rows as $row) {
            $objects[] = new $class($this->table, $row);
        }

        $this->setData($objects);
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

}
