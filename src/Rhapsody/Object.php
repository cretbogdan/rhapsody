<?php

namespace Rhapsody;

use Doctrine\Common\Util\Inflector;

class Object
{
    private $data = array();
    private $columns;
    private $isNew = true;
    private $isModified = false;
    private $id;

    public $table;

    public function __construct($table = null, array $data = array(), $isNew = true)
    {
        if ($table) {
            $this->table = $table;
        }

        $this->setDefaultValues();

        foreach ($data as $column => $value) {
            $this->set($column, $value);
        }

        $this->isNew = $isNew;
    }

    public function fromArray(array $data)
    {
        foreach ($data as $column => $value) {
            $this->set($column, $value);
        }
    }

    public function toArray()
    {
        $data = array();
        foreach ($this->getColumnNames() as $column) {
            $data[$column] = $this->get($column);
        }

        return $data;
    }


    /**
     * Save object to database
     */
    public function save()
    {
        $databaseData = $this->getDatabaseConvertedData();

        if ($this->isNew()) {
            unset($databaseData['id']);

            if ($this->hasColumn('created_at')) {
                $databaseData['created_at'] = date('Y-m-d H:i:s');
            }

            if ($this->hasColumn('updated_at')) {
                $databaseData['updated_at'] = date('Y-m-d H:i:s');
            }

            Rhapsody::getConnection()->insert($this->table, $databaseData);
            $this->data['id'] = (int) Rhapsody::getConnection()->lastInsertId();
        } elseif ($this->isModified()) {
            if ($this->hasColumn('updated_at')) {
                $databaseData['updated_at'] = date('Y-m-d H:i:s');
            }

            Rhapsody::getConnection()->update($this->table, $databaseData, array('id' => $databaseData['id']));
        } else {
            // Nothing modified
        }

        $this->isModified = false;
        $this->isNew = false;
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
        return $this->get('id') ? false : true;
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

        return $this->isModified;
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
            $newValue = $this->getColumnType($name)->convertToPHPValue($value, Rhapsody::getConnection()->getDatabasePlatform());

            if (isset($this->data[$name]) && $newValue != $this->data[$name]) {
                $this->isModified = true;
            }

            $this->data[$name] = $newValue;

            return $this;
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
        foreach ($this->getColumns() as $column) {
            $this->set($column->getName(), $column->getDefault());
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

    public function __call($name, $arguments)
    {
        if (0 === strpos($name, 'set')) {
            $column = preg_replace('/set/', '', $name, 1);

            return $this->set($column, $arguments[0]);
        }

        if (0 === strpos($name, 'get')) {
            $column = preg_replace('/get/', '', $name, 1);

            return $this->get($column);
        }

        throw new \BadMethodCallException("Method $name does not exist on \Rhapsody\Object");
    }

    protected function getTable()
    {
        return $this->table;
    }
}
