<?php

namespace Rhapsody;

class Object
{
    public $table;
    public $data;

    public function __construct($table = null, array $data = array())
    {
        $this->table = $table;
        $this->data = $data;
    }

    public function save()
    {
        if (isset($this->id)) {
            Rhapsody::getConnection()->update($this->table, $this->data, array('id' => $this->id));
        } else {
            Rhapsody::getConnection()->insert($this->table, $this->data);
        };
    }


    public function __set($name, $value)
    {
        $this->data[$name] = $value;
    }

    public function __get($name)
    {
        if (array_key_exists($name, $this->data)) {
            return $this->data[$name];
        }

        $trace = debug_backtrace();
        trigger_error(
            'Undefined property via __get(): ' . $name .
            ' in ' . $trace[0]['file'] .
            ' on line ' . $trace[0]['line'],
            E_USER_NOTICE);
        return null;
    }

    public function __isset($name)
    {
        return isset($this->data[$name]);
    }

    public function __unset($name)
    {
        unset($this->data[$name]);
    }
}
