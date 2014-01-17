<?php

namespace Rhapsody\Object;

use Doctrine\Common\Util\Inflector;
use Rhapsody;
use Rhapsody\Object;
use Rhapsody\RhapsodyException;

class BaseObject
{
    protected $columnData = array();
    protected $virtualColumnData = array();
    protected $toDelete = array(); // to delete objects on save
    protected $toSave = array(); // to delete objects on save
    protected $isNew = true;
    protected $isModified = false;
    protected $virtualColumns = array();

    public $table;

    public function __construct($table = null, array $columnData = array(), $isNew = true)
    {
        if ($table) {
            $this->table = $table;
        }

        $this->setDefaultValues();
        $this->fromArray($columnData);

        $this->isNew = $isNew;
    }

    public function addVirtualColumns(array $columns)
    {
        $this->virtualColumns = array_merge($this->virtualColumns, $columns);
    }

    public function fromArray(array $columnData = array())
    {
        foreach ($columnData as $column => $value) {
            $this->set($column, $value);
        }
    }

    /**
     * Save object to database
     */
    public function save()
    {
        $this->baseSave();

        foreach ($this->toDelete as $object) {
            $object->delete();
        }
        $this->toDelete = array();

        foreach ($this->toSave as $object) {
            $object->save();
        }

        $this->toSave = array();

        $this->isModified = false;
        $this->isNew = false;

        return $this;
    }

    public function delete()
    {
        if ($this->isNew()) {
            return $this;
        }

        Rhapsody::getConnection()->delete($this->getTable(), array('id' => $this->id));

        return $this;
    }

    /**
     * Basic column save for current object
     */
    protected function baseSave()
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

            $this->columnData['id'] = (int) Rhapsody::getConnection()->lastInsertId();
            Rhapsody::getObjectCache()->saveObject($this);
        } elseif ($this->isModified()) {
            if ($this->hasColumn('updated_at')) {
                $databaseData['updated_at'] = date('Y-m-d H:i:s');
            }

            Rhapsody::getConnection()->update($this->table, $databaseData, array('id' => $databaseData['id']));
        } else {
            // Nothing modified
        }
    }

    private function getDatabaseConvertedData()
    {
        $databaseData = array();

        foreach ($this->columnData as $column => $value) {
            $databaseData[$column] = $this->getColumnType($column)->convertToDatabaseValue($value, Rhapsody::getConnection()->getDatabasePlatform());
        }

        return $databaseData;
    }

    public function toArray()
    {
        return $this->columnData;
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
     * Check if object has column or related object
     *
     * @param  string  $name
     *
     * @return boolean
     */
    public function has($name)
    {
        $name = Inflector::tableize($name);

        if ($this->hasColumn($name)) {
            return true;
        }

        if ($this->hasVirtualColumn($name)) {
            return true;
        }

        return false;
    }

    /**
     * Set field value
     *
     * @param string $name
     * @param string|integer|float $value
     *
     * @throws RhapsodyException
     *
     * @return $this
     */
    public function set($name, $value)
    {
        $name = Inflector::tableize($name);

        if ($this->hasColumn($name)) {
            $newValue = $this->getColumnType($name)->convertToPHPValue($value, Rhapsody::getConnection()->getDatabasePlatform());

            if ((isset($this->columnData[$name]) || array_key_exists($name, $this->columnData)) && $newValue != $this->columnData[$name]) {
                $this->isModified = true;
            }

            $this->columnData[$name] = $newValue;

            return $this;
        }

        if ($this->hasVirtualColumn($name)) {
            $this->setVirtualColumn($name, $value);

            return $this;
        }

        throw new RhapsodyException("Column \"$name\" does not exist for table \"$this->table\"!");
    }

    public function populateVirtualColumns(array $virtualColumnData)
    {
        $keys = array_map(function ($column) {
            return Inflector::tableize($column);
        }, array_keys($virtualColumnData));

        $virtualColumnData = array_combine($keys, $virtualColumnData);


        foreach ($this->virtualColumns as $name) {
            if (in_array($name, $keys)) {
                $this->setVirtualColumn($name, $virtualColumnData[$name]);
            }
        }
    }

    public function hasVirtualColumns(array $names)
    {
        foreach ($names as $name) {
            if (! $this->hasVirtualColumn($name)) {
                return false;
            }
        }

        return true;
    }

    public function hasVirtualColumn($name)
    {
        $name = Inflector::tableize($name);

        return in_array($name, $this->virtualColumns);
    }

    public function setVirtualColumn($name, $value)
    {
        if (! $this->hasVirtualColumn($name)) {
            throw new RhapsodyException("No virtual column \"$name\"");
        }

        $name = Inflector::tableize($name);
        $this->virtualColumnData[$name] = $value;
    }

    public function getVirtualColumn($name)
    {
        if (! $this->hasVirtualColumn($name)) {
            throw new RhapsodyException("No virtual column \"$name\"");
        }

        $name = Inflector::tableize($name);
        return $this->virtualColumnData[$name];
    }

    public function getId()
    {
        return $this->get('id');
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
            return $this->columnData[$name];
        }

        if ($this->hasVirtualColumn($name)) {
            return $this->getVirtualColumn($name);
        }

        throw new RhapsodyException("Column \"$name\" does not exist for table \"$this->table\"!");
    }

    protected function hasObjectDelete(Object $object)
    {
        return in_array($object, $this->toDelete, true);
    }

    protected function addObjectDelete(Object $object)
    {
        if ($this->hasObjectDelete($object)) {
            return;
        }

        $this->toDelete[] = $object;
    }

    protected function removeObjectDelete(Object $object)
    {
        $key = array_search($object, $this->toDelete, true);

        if ($key) {
            unset($this->toDelete[$key]);
        }
    }

    protected function hasObjectSave(Object $object)
    {
        return in_array($object, $this->toSave, true);
    }

    protected function addObjectSave(Object $object)
    {
        if ($this->hasObjectSave($object)) {
            return;
        }

        $this->toSave[] = $object;
    }

    protected function removeObjectSave(Object $object)
    {
        $key = array_search($object, $this->toSave, true);

        if ($key) {
            unset($this->toSave[$key]);
        }
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
        return $this->getColumn($name)->getType();
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
        unset($this->columnData[$name]);
    }

    public function __call($name, $arguments)
    {
        if (0 === strpos($name, 'set')) {
            $column = preg_replace('/set/', '', $name, 1);

            return $this->set($column, $arguments[0]);
        }

        if (0 === strpos($name, 'add')) {
            $column = preg_replace('/add/', '', $name, 1);

            if ($this->hasChildren($column)) {
                return $this->addChild($arguments[0]);
            } else {
                return $this->addForeignObject($arguments[0]);
            }
        }

        if (0 === strpos($name, 'remove')) {
            $column = preg_replace('/remove/', '', $name, 1);

            if ($this->hasChildren($column)) {
                return $this->removeChild($arguments[0]);
            } else {
                return $this->removeForeignObject($arguments[0]);
            }
        }

        if (0 === strpos($name, 'get')) {
            $column = preg_replace('/get/', '', $name, 1);

            return $this->get($column);
        }

        if (0 === strpos($name, 'has')) {
            $column = preg_replace('/has/', '', $name, 1);

            return $this->has($column);
        }

        throw new RhapsodyException("Method $name does not exist on \Rhapsody\Object");
    }

    public function getTable()
    {
        return $this->table;
    }
}
