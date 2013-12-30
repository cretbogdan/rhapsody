<?php

namespace Rhapsody\Object;

use Doctrine\Common\Util\Inflector;
use Rhapsody;
use Rhapsody\Collection;
use Rhapsody\Object;
use Rhapsody\RhapsodyException;

class ChildrenAwareObject extends ParentAwareObject
{
    private $children = array();

    public function has($name)
    {
        if (parent::has($name)) {
            return true;
        }

        return $this->hasChildren($table);
    }

    public function get($name)
    {
        try {
            return parent::get($name);
        } catch (RhapsodyException $exception) {
            if (! $this->hasChildren($name)) {
                throw $exception;
            }

            return $this->getChildren($name);
        }
    }

    public function set($name, $value)
    {
        try {
            return parent::set($name, $value);
        } catch (RhapsodyException $exception) {
            if (! $value instanceof Collection || ! $this->hasChildren($value->getTable())) {
                throw $exception;
            }

            return $this->setChildren($value);
        }
    }

    public function findChildren($table)
    {
        $this->validateChildrenTable($table);
        $tableName = $this->getChildrenTableName($table);

        $this->children[$tableName] = Rhapsody::query($tableName)->filterBy($this->getTable().'_id', $this->id)->find();
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
        $tableName = $this->getChildrenTableName($table);

        if (! isset($this->children[$tableName])) {
            $this->findChildren($tableName);
        }

        return $this->children[$tableName];
    }

    /**
     * Set the children of a given table to current object
     *
     * @param Collection $children
     */
    public function setChildren(Collection $children)
    {
        $table = $children->getTable();
        $this->validateChildrenTable($table);
        $tableName = $this->getChildrenTableName($table);
        $currentChildren = $this->getChildren($table);

        foreach ($children as $child) {
            $this->addChild($child);
            $children->remove($child);
        }

        return $this;
    }

    public function hasChild(Object $child)
    {
        $currentChildren = $this->getChildren($child->getTable());

        return $currentChildren->contains($child);
    }

    public function addChild(Object $child)
    {
        if ($this->hasChild($child)) {
            return $this;
        }

        $currentChildren = $this->getChildren($child->getTable());
        $this->addObjectSave($child);
        $currentChildren->add($child);

        $parent = $child->getParent($this->getTable());

        if ($parent !== $this) {
            $child->setParent($this);

            if ($parent) {
                $this->addObjectSave($parent);
            }
        }

        return $this;
    }

    public function removeChild(Object $child)
    {
        $this->getChildren($child->getTable())->remove($child);
        $child->set($this->getChildrenColumn(), null);
        $child->clearParent($this->getTable());

        if ($child->isNew()) {
            $this->removeObjectSave($child);
        } else {
            $this->addObjectSave($child);
        }
    }

    protected function validateChildrenTable($table)
    {
        if (! $this->hasChildren($table)) {
            throw new RhapsodyException(sprintf("Cannot get children '%s' for table '$table'!", $this->getChildrenTableName($table)));
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
        $table = $this->getChildrenTableName($table);

        if (! Rhapsody::getTableManager()->tablesExist($table)) {
            return false;
        }

        $table = Rhapsody::getTableManager()->getTable($table);

        if (! $table || ! $table->hasColumn($this->getChildrenColumn())) {
            return false;
        }

        return true;
    }

    public function getChildrenTableName($table)
    {
        $table = Inflector::tableize($table);
        if ('s' === substr($table, -1)) {
            $table = substr($table, 0, -1);
        }

        return $table;
    }

    public function getChildrenColumn()
    {
        return $this->getTable().'_id';
    }
}
