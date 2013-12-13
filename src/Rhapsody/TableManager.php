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

    public function getTable($name)
    {
        $tables = $this->getSchemaManager()->listTables();

        foreach ($tables as $table) {
            if ($name == $table->getName()) {
                return $table;
            }
        }

        throw new \InvalidArgumentException("Table $name does not exist!");
    }

    public function tablesExist($tableNames)
    {
        return $this->getSchemaManager()->tablesExist($tableNames);
    }

    public function getColumns($table)
    {
        if (! isset($this->columns[$table])) {
            $this->columns[$table] = $this->getSchemaManager()->listTableColumns($table);
        }

        return $this->columns[$table];
    }

    public function dropTable($name)
    {
        $this->getSchemaManager()->dropTable($name);
    }

    public function getSchemaManager()
    {
        return $this->conn->getSchemaManager();
    }
}
