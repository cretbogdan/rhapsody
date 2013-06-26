<?php

namespace Rhapsody;

use Doctrine\Common\Util\Inflector;

class Object
{
    public $table;
    public $data;

    public function __construct($table = null, array $data = array())
    {
        if ($table) {
            $this->table = $table;
        }

        $this->data = $data;
    }


    /**
     * Save object to database
     */
    public function save()
    {
        if (isset($this->data['id'])) {
            Rhapsody::getConnection()->update($this->table, $this->data, array('id' => $this->data['id']));
        } else {
            Rhapsody::getConnection()->insert($this->table, $this->data);
            $this->data['id'] = Rhapsody::getConnection()->lastInsertId();
        };
    }


    /**
     * Delete from database
     */
    public function delete()
    {
        if (isset($this->data['id'])) {
            Rhapsody::getConnection()->delete($this->table, array('id' => $this->data['id']));
        }
    }

    // Overload functions

    public function __set($name, $value)
    {
        $name = Inflector::tableize($name);
        $this->data[$name] = $value;
    }

    public function __get($name)
    {
        $name = Inflector::tableize($name);
        if (array_key_exists($name, $this->data)) {
            return $this->data[$name];
        }

        $trace = debug_backtrace();
        trigger_error(
            'Undefined field or relation object ' . $name .
            ' in ' . $trace[0]['file'] .
            ' on line ' . $trace[0]['line'],
            E_USER_NOTICE);

        return null;
    }

    public function __isset($name)
    {
        $name = Inflector::tableize($name);
        return isset($this->data[$name]);
    }

    public function __unset($name)
    {
        $name = Inflector::tableize($name);
        unset($this->data[$name]);
    }
}
