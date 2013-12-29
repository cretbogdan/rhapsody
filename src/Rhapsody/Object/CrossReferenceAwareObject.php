<?php

namespace Rhapsody\Object;

use Doctrine\Common\Util\Inflector;

class CrossReferenceAwareObject extends ChildrenAwareObject
{
    private $foreignObjects = array();
    private $referenceObjects = array();

    // public function save()
    // {
    //     foreach ($this->children as $tableName => $children) {
    //         unset($this->children[$tableName]);
    //         $children->save();
    //     }

    //     return parent::save();
    // }

    public function has($name)
    {
        if (parent::has($name)) {
            return true;
        }

        return $this->hasCrossReferenceObjects($table);
    }

    public function get($name)
    {
        try {
            return parent::get($name);
        } catch (RhapsodyException $exception) {
            if (! $this->hasCrossReferenceObjects($name)) {
                throw $exception;
            }

            return $this->getCrossReferenceObjects($name);
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

            return $this->setCrossReferenceObjects($name, $value);
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
        return $this->getReferenceTableName($table) ? true : false;
    }

    // public function getCrossReferenceObjects($table)
    // {
    //     $table = $this->getReferencedForeignTableName($table);
    //     $referenceTable = $this->getReferenceTableName($table);
    //     $idColumn = $this->table.'_id';

    //     if (! isset($this->crossReferenceObjects[$referenceTable])) {
    //         $referenceObjects = Rhapsody::query($referenceTable)->filterBy($idColumn)->find();
    //         $ids = $referenceObjects->toColumnValues('id');
    //         $foreignObjects = Rhapsody::query($table)->filterById(implode(',', $ids), 'in')->find();

    //         foreach ($foreignObjects as $object) {
    //             $this->addCrossReferenceObject($table, $object);
    //         }
    //     }

    //     return $this->crossReferenceObjects[$referenceTable];
    // }

    // public function addCrossReferenceObject($table, Object $object)
    // {
    //     $table = $this->getReferencedForeignTableName($table);

    //     if (! isset($this->crossReferenceObjects[$table])) {
    //         $this->crossReferenceObjects[$table] = Rhapsody::createCollection($table);
    //     }

    //     if (! $this->crossReferenceObjects[$table]->containsObjectId($object)) {
    //         $this->crossReferenceObjects[$table]->add($object);
    //     }
    // }

    // public function setCrossReferenceObjects($table, Collection $crossReferenceObjects)
    // {
    //     $table = $this->getReferencedForeignTableName($table);
    //     $referenceTable = $this->getReferenceTableName($table);

    //     $idColumn = $this->table.'_id';

    //     $referenceObjects = Rhapsody::query($referenceTable)->filterBy($idColumn)->find();
    //     $referenceObjectIds = $referenceObjects->toColumnValues('id');

    //     $currentCrossReferenceObjects = $this->getCrossReferenceObjects($table);

    //     foreach ($referenceObjects as $object) {
    //         $this->addObjectDelete($object);
    //     }

    //     foreach ($currentCrossReferenceObjects as $object) {
    //         if ($crossReferenceObjects->containsObjectId($object)) {
    //             $this->addCrossReferenceObject($table, $object);
    //         } else {
    //             foreach ($referenceObjects as $referenceObject) {
    //                 if ($object->id == $referenceObject->get($idColumn)) {
    //                     $this->addObjectDelete($referenceObject);
    //                     break;
    //                 }
    //             }
    //         }

    //         $crossReferenceObjects->remove($object);
    //     }
    // }


    // private function getReferencedForeignTableName($table)
    // {
    //     $table = Inflector::tableize($table);
    //     if ('s' === substr($table, -1)) {
    //         $table = substr($table, 0, -1);
    //     }

    //     return $table;
    // }


    // public function getReferenceTableName($table)
    // {
    //     $table = $this->getReferencedForeignTableName($table);
    //     $referenceTable = $this->table.'_'.$table;

    //     if (Rhapsody::getTableManager()->tablesExist($referenceTable)) {
    //         return $referenceTable;
    //     }

    //     $referenceTable = $table.'_'.$this->table;
    //     if (Rhapsody::getTableManager()->tablesExist($referenceTable)) {
    //         return $referenceTable;
    //     }

    //     return null;
    // }
}
