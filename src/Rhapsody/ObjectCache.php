<?php

namespace Rhapsody;

use Doctrine\Common\Cache\ArrayCache;
use Rhapsody\Object;

class ObjectCache extends ArrayCache
{
    public function saveObject(Object $object)
    {
        if ($object->id) {
            $this->save($this->getCacheId($object), $object);
        }
    }

    public function containsObject($id, $table)
    {
        return $this->contains($this->getCacheId($id, $table));
    }

    public function fetchObject($id, $table)
    {
        $cacheId = $this->getCacheId($id, $table);

        return $this->contains($cacheId) ? $this->fetch($cacheId) : null;
    }

    private function getCacheId($id, $table = null)
    {
        if ($id instanceof Object) {
            $cacheId = $id->getTable().'_'.$id->id;
        } else {
            $cacheId = $table.'_'.$id;
        }

        return $cacheId;
    }
}
