<?php

namespace Rhapsody;

class Query
{
    private $table;
    private $limit;

    public function __construct($table)
    {
        $this->table = $table;
    }

    public static function create($table)
    {
        return new static($table);
    }

    public function findOne()
    {
        $query = "SELECT * FROM `{$this->table}` LIMIT 1";
        $data = Rhapsody::getConnection()->fetchAssoc($query);

        return new Object($this->table, $data);
    }
}
