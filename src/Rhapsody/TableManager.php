<?php

namespace Rhapsody;

use Doctrine\DBAL\Connection;

class TableManager
{
    private $schemaManager;
    private $columns = array();

    public function __construct(Connection $conn)
    {
        $this->conn = $conn;
    }

    public function getColumns($table)
    {
        if (! isset($this->columns[$table])) {
            $this->columns[$table] = $this->getSchemaManager()->listTableColumns($table);
        }

        return $this->columns[$table];
    }

    private function getSchemaManager()
    {
        return $this->conn->getSchemaManager();
    }
}
