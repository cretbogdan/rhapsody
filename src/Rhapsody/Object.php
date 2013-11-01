<?php

namespace Rhapsody;

use Doctrine\Common\Util\Inflector;

class Object
{
    public $table;
    private $data = array();
    private $initialData = array();
    private $columns;

    public function __construct($table = null, array $data = array())
    {
        if ($table) {
            $this->table = $table;
        }

        $this->setDefaultValues();

        foreach ($data as $column => $value) {
            $this->set($column, $value);
        }

        $this->initialData = $data;
    }


    /**
     * Save object to database
     */
    public function save()
    {
        $databaseData = $this->getDatabaseConvertedData();

        if ($this->isNew()) {
            Rhapsody::getConnection()->insert($this->table, $databaseData);
            $this->data['id'] = Rhapsody::getConnection()->lastInsertId();
        } elseif ($this->isModified()) {
            Rhapsody::getConnection()->update($this->table, $databaseData, array('id' => $databaseData['id']));
        } else {
            // Nothing modified
        }
    }


    private function getDatabaseConvertedData()
    {
        $databaseData = array();

        foreach ($this->data as $column => $value) {
            $databaseData[$column] = $this->getColumnType($column)->convertToDatabaseValue($value, Rhapsody::getConnection()->getDatabasePlatform());
        }

        return $databaseData;
    }


    /**
     * Delete from database
     */
    public function delete()
    {
        if (! $this->isNew()) {
            Rhapsody::getConnection()->delete($this->table, array('id' => $this->data['id']));
        }
    }

    /**
     * Check if object is new
     *
     * @return boolean
     */
    public function isNew()
    {
        return empty($this->initialData);
    }

    /**
     * Check if object is modified
     *
     * @return boolean
     */
    public function isModified()
    {
        if ($this->isNew()) {
            return true;
        }

        foreach ($this->data as $column => $value) {
            if (isset($this->initialData[$column])) {
                if ($this->initialData[$column] != $value) {
                    return true;
                }
            } else {
                return true;
            }
        }

        return false;
    }

    /**
     * Set field value
     *
     * @param string                $name
     * @param string|integer|float  $value
     */
    public function set($name, $value)
    {
        $name = Inflector::tableize($name);

        if ($this->hasColumn($name)) {
            $this->data[$name] = $this->getColumnType($name)->convertToPHPValue($value, Rhapsody::getConnection()->getDatabasePlatform());

            return;
        }

        throw new \InvalidArgumentException("Column $name does not exist!");
    }

    /**
     * Get field value if exists, NULL otherwise
     *
     * @param  string $name
     *
     * @return string|null
     */
    public function get($name)
    {
        $name = Inflector::tableize($name);
        if ($this->hasColumn($name)) {
            return $this->data[$name];
        }

        throw new \InvalidArgumentException('Undefined field or relation object '.$name);
    }

    public function has($name)
    {
        $name = Inflector::tableize($name);
        return isset($this->data[$name]);
    }

    public function hasColumn($name)
    {
        return in_array($name, $this->getColumnNames());
    }

    public function getColumn($name)
    {
        $columns = $this->getColumns();

        return $columns[$name];
    }

    public function getColumnType($name)
    {
        $columns = $this->getColumns();

        return $columns[$name]->getType();
    }

    public function getColumnNames()
    {
        return array_keys($this->getColumns());
    }

    public function getColumns()
    {
        return Rhapsody::getTableManager()->getColumns($this->getTable());
    }

    private function setDefaultValues()
    {
        foreach ($this->getColumnNames() as $column) {
            $this->set($column, null);
        }
    }

    // Overload functions

    public function __set($name, $value)
    {
        $this->set($name, $value);
    }

    public function __get($name)
    {
        return $this->get($name);
    }

    public function __isset($name)
    {
        return $this->has($name);
    }

    public function __unset($name)
    {
        $name = Inflector::tableize($name);
        unset($this->data[$name]);
    }

    protected function getTable()
    {
        return $this->table;
    }
}
