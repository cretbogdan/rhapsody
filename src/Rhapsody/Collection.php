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


    public function save()
    {
        Rhapsody::getConnection()->beginTransaction();
        foreach ($this->getData() as $object) {
            $object->save();
        }
        Rhapsody::getConnection()->commit();
    }

    public function fromArray($rows)
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
