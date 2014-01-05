<?php

namespace Rhapsody;

use Doctrine\DBAL\Connection;

class TableManager
{
    private $schemaManager;
    private $columns = array();
    private $tablesExists = array();
    private $tables;

    public function __construct(Connection $conn)
    {
        $this->conn = $conn;
    }

    public function getTable($name)
    {
        foreach ($this->getTables() as $table) {
            if ($name == $table->getName()) {
                return $table;
            }
        }

        throw new \InvalidArgumentException("Table $name does not exist!");
    }

    public function getTables()
    {
        if (null === $this->tables) {
            $this->tables = $this->getSchemaManager()->listTables();
        }

        return $this->tables;
    }

    public function tablesExist($tableNames)
    {
        if (! isset($this->tablesExists[$tableNames])) {
            $this->tablesExists[$tableNames] = $this->getSchemaManager()->tablesExist($tableNames);
        }

        return $this->tablesExists[$tableNames];
    }

    public function hasColumn($table, $column)
    {
        return in_array($column, array_keys($this->getColumns($table)));
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
