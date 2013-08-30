<?php

namespace Rhapsody;

use Doctrine\Common\Util\Inflector;

class Object
{
    public $table;
    public $data = array();

    public function __construct($table = null, array $data = array())
    {
        if ($table) {
            $this->table = $table;
        }

        foreach ($data as $column => $value) {
            $this->set($column, $value);
        }
    }


    /**
     * Save object to database
     */
    public function save()
    {
        if ($this->isNew()) {
            Rhapsody::getConnection()->insert($this->table, $this->data);
            $this->data['id'] = Rhapsody::getConnection()->lastInsertId();
        } else {
            Rhapsody::getConnection()->update($this->table, $this->data, array('id' => $this->data['id']));
        };
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
        return isset($this->data['id']) ? false : true;
    }

    public function set($name, $value)
    {
        $name = Inflector::tableize($name);

        if (is_bool($value)) {
            $value = (int) $value;
        }

        $this->data[$name] = $value;
    }

    public function get($name)
    {
        $name = Inflector::tableize($name);
        if (array_key_exists($name, $this->data)) {
            return $this->data[$name];
        }

        // TODO: No errors for new object (e.g. symfony form builder new object)
        // $trace = debug_backtrace();
        // trigger_error(
        //     'Undefined field or relation object ' . $name .
        //     ' in ' . $trace[0]['file'] .
        //     ' on line ' . $trace[0]['line'],
        //     E_USER_NOTICE);

        return null;
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
        $name = Inflector::tableize($name);
        return isset($this->data[$name]);
    }

    public function __unset($name)
    {
        $name = Inflector::tableize($name);
        unset($this->data[$name]);
    }
}
