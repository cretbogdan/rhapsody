<?php

namespace Rhapsody;

use Doctrine\Common\Util\Inflector;

class Object
{
    private $data = array();
    private $parents = array();
    private $children = array();
    private $crossReferenceObjects = array();
    private $toDelete = array(); // to delete objects on save
    private $isNew = true;
    private $isModified = false;

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

    /**
     * Populate object from array
     *
     * @param  array  $data
     */
    public function fromArray(array $data)
    {
        foreach ($data as $column => $value) {
            $this->set($column, $value);
        }
    }

    /**
     * Get an array with data about the object
     *
     * @return array
     */
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

        foreach ($this->children as $tableName => $children) {
            unset($this->children[$tableName]);
            $children->save();
        }

        foreach ($this->parents as $tableName => $parent) {
            unset($this->parents[$tableName]);
            $parent->save();
        }

        foreach ($this->toDelete as $object) {
            $object->delete();
        }

        $this->isModified = false;
        $this->isNew = false;

        return $this;
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

        if ($this->hasParent($name)) {
            return true;
        }

        if ($this->hasChildren($name)) {
            return true;
        }

        if ($this->hasCrossObjects($name)) {
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
     * @throws \InvalidArgumentException
     *
     * @return $this
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

        if ($this->hasParent($name)) {
            $this->setParent($name, $value);

            return $this;
        }

        if ($this->hasChildren($name)) {
            $this->setChildren($name, $value);

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

        if ($this->hasParent($name)) {
            return $this->getParent($name);
        }

        if ($this->hasChildren($name)) {
            return $this->getChildren($name);
        }

        throw new \InvalidArgumentException('Undefined field or relation object '.$name);
    }


    /**
     * Get the parent object of a table.
     *
     * e.g. The 'author' parent from a 'book' object
     *
     * @param  string $table
     *
     * @return Object
     */
    public function getParent($table)
    {
        $table = Inflector::tableize($table);
        $this->validateParent($table);
        $columnName = $this->getParentColumnName($table);

        if (! isset($this->parents[$columnName])) {
            $this->parents[$columnName] = Rhapsody::query($table)->filterById($this->get($columnName))->findOne();
        }

        return $this->parents[$columnName];
    }

    /**
     * Set the parent of current object for a given table
     *
     * @param string $table
     * @param \Rhapsody\Object $object
     *
     * @throws \UnexpectedValueException
     *
     * @return $this
     */
    public function setParent($table, Object $object)
    {
        $table = Inflector::tableize($table);
        $this->validateParent($table);
        $columnName = $this->getParentColumnName($table);

        if ($table != $object->getTable()) {
            throw new \UnexpectedValueException("Expected table '$table' for Rhapsody\Object::setParent(). Table '".$object->getTable()."' given!");
        }

        if ($object->isNew()) {
            $object->save();
        }

        $this->parents[$columnName] = $object;
        $this->set($columnName, $object->id);

        return $this;
    }

    protected function validateParent($table)
    {
        if (! $this->hasParent($table)) {
            throw new \InvalidArgumentException(sprintf("Cannot get parent for table '$table'. Column '%' does not exist!", $this->getParentColumnName($table)));
        }
    }

    /**
     * Check if current object has a given table as parent
     *
     * @param  string  $table
     *
     * @return boolean
     */
    public function hasParent($table)
    {
        return $this->hasColumn($this->getParentColumnName($table));
    }

    public function getParentColumnName($table)
    {
        $table = Inflector::tableize($table);

        return $table.'_id';
    }

    /**
     * Get the children of current object for a given table
     *
     * e.g. Get the 'books' children for current 'author' object
     *
     * @param  string $table
     *
     * @return Collection
     */
    public function getChildren($table)
    {
        $table = Inflector::tableize($table);
        $this->validateChildren($table);
        $tableName = $this->getChildrenTableName($table);

        if (! isset($this->children[$tableName])) {
            $this->children[$tableName] = Rhapsody::query($tableName)->filterBy($this->getTable().'_id', $this->id)->find();
        }

        return $this->children[$tableName];
    }

    /**
     * Set the children of a given table to current object
     *
     * @param string     $table
     * @param Collection $children
     */
    public function setChildren($table, Collection $children)
    {
        $table = Inflector::tableize($table);
        $this->validateChildren($table);
        $tableName = $this->getChildrenTableName($table);

        if ($tableName != $children->getTable()) {
            throw new \UnexpectedValueException("Expected table '$tableName' for Rhapsody\Object::setChildren(). Table '".$children->getTable()."' given!");
        }

        $currentChildren = $this->getChildren($table);

        foreach ($currentChildren as $child) {
            $currentChildren->remove($child);
        }

        foreach ($children as $child) {
            $currentChildren->add($child);
            $children->remove($child);

            $child->set($this->getChildrenTableIdentifierColumnName(), $this->id);
            $child->setParent($this->getTable(), $this);
        }

        if ($this->isNew()) {
            $this->save(); // id needed
        }

        return $this;
    }

    protected function validateChildren($table)
    {
        if (! $this->hasChildren($table)) {
            throw new \InvalidArgumentException(sprintf("Cannot get children '%s' for table '$table'!", $this->getChildrenTableName($table)));
        }
    }

    /**
     * Check if current object has children for given table
     *
     * @param  string  $table
     *
     * @return boolean
     */
    public function hasChildren($table)
    {
        $table = Inflector::tableize($table);
        $table = substr($table, 0, -1);

        if (! Rhapsody::getTableManager()->tablesExist($table)) {
            return false;
        }

        $table = Rhapsody::getTableManager()->getTable($table);
        if (! $table->hasColumn($this->getChildrenTableIdentifierColumnName())) {
            return false;
        }

        return true;
    }

    public function getChildrenTableName($table)
    {
        $table = Inflector::tableize($table);

        return substr($table, 0, -1);
    }

    public function getChildrenTableIdentifierColumnName()
    {
        return $this->getTable().'_id';
    }

    /**
     * Check if current object has cross related objects
     *
     * e.g. A table 'book' has cross related 'tag' objects through table 'book_tag'
     *
     * @param string $table
     *
     * @return bool
     */
    public function hasCrossReferenceObjects($table)
    {
        return $this->getReferenceTableName($table) ? true : false;
    }

    public function getCrossReferenceObjects($table)
    {
        $table = $this->getReferencedForeignTableName($table);
        $referenceTable = $this->getReferenceTableName($table);
        $idColumn = $this->table.'_id';

        if (! isset($this->crossReferenceObjects[$referenceTable])) {
            $referenceObjects = Rhapsody::query($referenceTable)->filterBy($idColumn)->find();
            $ids = $referenceObjects->toColumnValues('id');
            $foreignObjects = Rhapsody::query($table)->filterById(implode(',', $ids), 'in')->find();

            foreach ($foreignObjects as $object) {
                $this->addCrossReferenceObject($table, $object);
            }
        }

        return $this->crossReferenceObjects[$referenceTable];
    }

    public function addCrossReferenceObject($table, Object $object)
    {
        $table = $this->getReferencedForeignTableName($table);

        if (! isset($this->crossReferenceObjects[$table])) {
            $this->crossReferenceObjects[$table] = Rhapsody::createCollection($table);
        }

        if (! $this->crossReferenceObjects[$table]->containsObjectId($object)) {
            $this->crossReferenceObjects[$table]->add($object);
        }
    }

    public function setCrossReferenceObjects($table, Collection $crossReferenceObjects)
    {
        $table = $this->getReferencedForeignTableName($table);
        $referenceTable = $this->getReferenceTableName($table);

        $idColumn = $this->table.'_id';

        $referenceObjects = Rhapsody::query($referenceTable)->filterBy($idColumn)->find();
        $referenceObjectIds = $referenceObjects->toColumnValues('id');

        $currentCrossReferenceObjects = $this->getCrossReferenceObjects($table);

        foreach ($referenceObjects as $object) {
            $this->addObjectDelete($object);
        }

        foreach ($currentCrossReferenceObjects as $object) {
            if ($crossReferenceObjects->containsObjectId($object)) {
                $this->addCrossReferenceObject($table, $object);
            } else {
                foreach ($referenceObjects as $referenceObject) {
                    if ($object->id == $referenceObject->get($idColumn)) {
                        $this->addObjectDelete($referenceObject);
                        break;
                    }
                }
            }

            $crossReferenceObjects->remove($object);
        }
    }

    private function addObjectDelete(Object $object)
    {
        if (in_array($object, $this->toDelete, true)) {
            return;
        }

        $this->toDelete[] = $object;
    }

    private function getReferencedForeignTableName($table)
    {
        $table = Inflector::tableize($table);
        if ('s' === substr($table, -1)) {
            $table = substr($table, 0, -1);
        }

        return $table;
    }


    public function getReferenceTableName($table)
    {
        $table = $this->getReferencedForeignTableName($table);
        $referenceTable = $this->table.'_'.$table;

        if (Rhapsody::getTableManager()->tablesExist($referenceTable)) {
            return $referenceTable;
        }

        $referenceTable = $table.'_'.$this->table;
        if (Rhapsody::getTableManager()->tablesExist($referenceTable)) {
            return $referenceTable;
        }

        return null;
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

    public function getTable()
    {
        return $this->table;
    }
}
