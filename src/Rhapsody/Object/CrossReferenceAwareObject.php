<?php

namespace Rhapsody\Object;

use Doctrine\Common\Util\Inflector;
use Rhapsody;
use Rhapsody\RhapsodyException;
use Rhapsody\Object;
use Rhapsody\Collection;

class CrossReferenceAwareObject extends ChildrenAwareObject
{
    private $foreignObjects = array();
    private $referenceObjects = array();

    public function has($name)
    {
        if (parent::has($name)) {
            return true;
        }

        return $this->hasCrossReferenceObjects($name);
    }

    public function get($name)
    {
        try {
            return parent::get($name);
        } catch (RhapsodyException $exception) {
            if (! $this->hasCrossReferenceObjects($name)) {
                throw $exception;
            }

            return $this->getForeignObjects($name);
        }
    }

    public function set($name, $value)
    {
        try {
            return parent::set($name, $value);
        } catch (RhapsodyException $exception) {
            if (! $this->hasCrossReferenceObjects($name)) {
                throw $exception;
            }

            return $this->setForeignObjects($value);
        }
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
        $referenceTable = $this->getReferenceTable($table);

        return $referenceTable ? true : false;
    }

    public function getReferenceObjects($table)
    {
        $this->validateCrossReferenceTable($table);
        $table = $this->getReferenceTable($table);

        if (! isset($this->referenceObjects[$table])) {
            if ($this->id) {
                $this->referenceObjects[$table] = Rhapsody::query($table)->filterBy($this->getTable().'_id', $this->id)->find();
            } else {
                $this->referenceObjects[$table] = Rhapsody::createCollection($table);
            }
        }

        return $this->referenceObjects[$table];
    }

    public function getForeignObjects($table)
    {
        $this->validateCrossReferenceTable($table);
        $foreignTable = $this->cleanTableName($table);

        if (! isset($this->foreignObjects[$foreignTable])) {
            $this->foreignObjects[$foreignTable] = Rhapsody::createCollection($foreignTable);

            $referenceObjectIds = $this->getReferenceObjects($table)->toColumnValues($foreignTable.'_id');
            $referenceObjectIds = array_unique($referenceObjectIds);


            if (! empty($referenceObjectIds)) {
                $sql = 'id in ('.implode(',', $referenceObjectIds).')';
                $foreignObjects = Rhapsody::query($foreignTable)->where($sql)->find();

                foreach ($foreignObjects as $foreignObject) {
                    $this->foreignObjects[$foreignTable]->add($foreignObject);
                }
            }
        }

        return $this->foreignObjects[$foreignTable];
    }

    public function setForeignObjects(Collection $objects)
    {
        $this->validateCrossReferenceTable($objects->getTable());
        $currentForeignObjects = $this->getForeignObjects($objects->getTable());

        $toRemoveForeignObjects = $currentForeignObjects->diff($objects);
        foreach ($toRemoveForeignObjects as $toRemoveForeignObject) {
            $this->removeForeignObject($toRemoveForeignObject);
        }

        $toAddForeignObjects = $objects->diff($currentForeignObjects);
        foreach ($toAddForeignObjects as $toAddForeignObject) {
            $this->addForeignObject($toAddForeignObject);
        }

        return $this;
    }

    public function removeForeignObject(Object $object)
    {
        $this->validateCrossReferenceTable($object->getTable());
        $referenceObjects = $this->getReferenceObjects($object->getTable());

        foreach ($referenceObjects as $referenceObject) {
            if ($referenceObject->get($this->getTable().'_id') == $this->id) {
                $referenceObjects->remove($referenceObject);

                if ($referenceObject->isNew()) {
                    $this->removeObjectSave($referenceObject);
                } else {
                    $this->addObjectDelete($referenceObject);
                }

                $this->isModified = true;
                break;
            }
        }

        $foreignObjects = $this->getForeignObjects($object->getTable());
        $foreignObjects->remove($object);

        return $this;
    }

    public function addForeignObject(Object $object)
    {
        $this->validateCrossReferenceTable($object->getTable());
        $referenceObjects = $this->getReferenceObjects($object->getTable());

        if ($this->isNew()) {
            $this->save();
        }

        if ($object->isNew()) {
            $object->save();
        }

        $create = true;
        foreach ($referenceObjects as $referenceObject) {
            if ($referenceObject->get($this->getTable().'_id') == $this->id
                && $referenceObject->get($object->getTable().'_id') == $object->id) {
                $create = false;
                break;
            }
        }

        if ($create) {
            $referenceObject = Rhapsody::create($this->getReferenceTable($object->getTable()))
                ->set($this->getTable().'_id', $this->id)
                ->set($object->getTable().'_id', $object->id);

            $referenceObjects->add($referenceObject);

            $this->addObjectSave($referenceObject);
            $this->isModified = true;
        }

        $foreignObjects = $this->getForeignObjects($object->getTable());
        if (! $foreignObjects->contains($object)) {
            $foreignObjects->add($object);
        }

        return $this;
    }

    private function validateCrossReferenceTable($table)
    {
        if (! $this->hasCrossReferenceObjects($table)) {
            throw new RhapsodyException("Table \"$table\" has no relation with ".$this->getTable());
        }
    }

    public function getReferenceTable($table)
    {
        $manager = Rhapsody::getTableManager();
        $table = $this->cleanTableName($table);

        $referenceTable = $this->table.'_'.$table;

        if ($manager->tablesExist($referenceTable)) {
            if ($manager->hasColumn($referenceTable, $table.'_id') && $manager->hasColumn($referenceTable, $this->getTable().'_id')) {
                return $referenceTable;
            }
        }

        $referenceTable = $table.'_'.$this->table;
        if ($manager->tablesExist($referenceTable)) {
            if ($manager->hasColumn($referenceTable, $table.'_id') && $manager->hasColumn($referenceTable, $this->getTable().'_id')) {
                return $referenceTable;
            }
        }

        return null;
    }

    private function cleanTableName($table)
    {
        $table = Inflector::tableize($table);
        if ('s' === substr($table, -1)) {
            $table = substr($table, 0, -1);
        }

        return $table;
    }
}
