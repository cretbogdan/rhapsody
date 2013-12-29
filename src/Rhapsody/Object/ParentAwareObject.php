<?php

namespace Rhapsody\Object;

use Doctrine\Common\Util\Inflector;
use Rhapsody;
use Rhapsody\RhapsodyException;
use Rhapsody\Object;

class ParentAwareObject extends BaseObject
{
    private $parents = array();

    public function has($name)
    {
        if (parent::has($name)) {
            return true;
        }

        return $this->hasParent($table);
    }

    public function get($name)
    {
        try {
            return parent::get($name);
        } catch (RhapsodyException $exception) {
            if (! $this->hasParent($name)) {
                throw $exception;
            }

            return $this->getParent($name);
        }
    }

    public function set($name, $value)
    {
        try {
            return parent::set($name, $value);
        } catch (RhapsodyException $exception) {
            if (! $this->hasParent($name)) {
                throw $exception;
            }

            if (null === $value) {
                return $this->removeParent($name);
            }

            return $this->setParent($value);
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

    public function clearParent($table)
    {
        $this->validateParent($table);

        if (isset($this->parents[$table])) {
            unset($this->parents[$table]);
        }
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

        if (! isset($this->parents[$table])) {
            if ($this->get($columnName)) {
                $this->parents[$table] = Rhapsody::query($table)->filterById($this->get($columnName))->findOne();
            } else {
                $this->parents[$table] = null;
            }
        }

        return $this->parents[$table];
    }

    /**
     * Set the parent of current object for a given table
     *
     * @param \Rhapsody\Object $object
     *
     * @throws RhapsodyException
     *
     * @return $this
     */
    public function setParent(Object $object)
    {
        $this->validateParent($object->getTable());
        $columnName = $this->getParentColumnName($object->getTable());
        $currentParent = $this->getParent($object->getTable());

        if ($currentParent) {
            $currentParent->removeChild($this);
        }

        if ($object->isNew()) {
            $object->save(); // id needed
        }

        $this->parents[$object->getTable()] = $object;
        $this->set($columnName, $object->id);

        if (! $object->hasChild($this)) {
            $object->addChild($this);
        }

        return $this;
    }

    public function removeParent($table)
    {
        $table = Inflector::tableize($table);
        $this->validateParent($table);
        $columnName = $this->getParentColumnName($table);

        $this->clearParent($table);
        $this->set($columnName, null);

        return $this;
    }

    protected function validateParent($table)
    {
        if (! $this->hasParent($table)) {
            throw new RhapsodyException(sprintf("Cannot get parent for table '$table'. Column '%' does not exist!", $this->getParentColumnName($table)));
        }
    }

    public function getParentColumnName($table)
    {
        $table = Inflector::tableize($table);

        return $table.'_id';
    }
}
