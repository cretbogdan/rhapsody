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
}
