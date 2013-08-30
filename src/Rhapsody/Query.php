<?php

namespace Rhapsody;

use Doctrine\Common\Util\Inflector;

class Query
{
    public static $lastExecutedQuery;
    protected $table;
    private $limit;
    private $offset;
    private $filters = array();
    private $orderByColumns = array();
    private $queryBuilder;

    protected function __construct($table = null)
    {
        if ($table) {
            $table = Inflector::tableize($table);
            $this->table = $table;
        }
    }

    public static function create($table = null)
    {
        $class = Rhapsody::getQueryClass($table);

        return new $class($table);
    }


    /**
     * Truncate table
     */
    public function truncate()
    {
        return Rhapsody::getConnection()->executeUpdate("TRUNCATE TABLE `{$this->table}`");
    }


    /**
     * Add an order by column
     *
     * @param  string $column
     * @param  string $type
     *
     * @return Query
     */
    public function orderBy($column, $type = 'asc')
    {
        $column = Inflector::tableize($column);
        $this->orderByColumns[] = array('column' => $column, 'type' => $type);

        return $this;
    }


    /**
     * Filter the query by a given column.
     *
     * @param  string $column
     * @param  string $value
     * @param  string $comparison
     *
     * @return Query
     */
    public function filterBy($column, $value, $comparison = '=')
    {
        $column = Inflector::tableize($column);

        if (is_bool($value)) {
            $value = (int) $value;
        }

        if ('in' == strtolower($comparison) && is_array($value)) {
            $value = implode(',', $value);
        }

        return $this->where("`$column` $comparison ?", $value);
    }


    /**
     * Add a where condition
     *
     * @param  string $sql
     * @param  string/array $params
     *
     * @return Query
     */
    public function where($sql, $params = null)
    {
        if ($sql) {
            if (! is_array($params)) {
                $params = $params ? array($params) : array();
            }

            $this->filters[] = array('sql' => $sql, 'params' => $params);
        }

        return $this;
    }


    /**
     * Set the offset for current query
     *
     * @param  int $offset
     */
    public function offset($offset)
    {
        $this->offset = $offset;

        return $this;
    }

    /**
     * Set the limit for current query
     *
     * @param  int $limit
     */
    public function limit($limit)
    {
        $this->limit = $limit;

        return $this;
    }


    /**
     * Find a single record
     *
     * @return Object/null
     */
    public function findOne()
    {
        $this->limit(1);
        list($queryString, $params) = $this->getQueryString();

        $query = "SELECT * FROM `{$this->table}` ".$queryString;
        self::$lastExecutedQuery = $query;
        $data = Rhapsody::getConnection()->fetchAssoc($query, $params);

        return $data ? Rhapsody::create($this->table, $data) : null;
    }


    /**
     * Find all records matching the query conditions
     *
     * @return Collection
     */
    public function find()
    {
        list($queryString, $params) = $this->getQueryString();
        $query = "SELECT * FROM `{$this->table}` ".$queryString;
        $rows = Rhapsody::getConnection()->fetchAll($query, $params);
        self::$lastExecutedQuery = $query;

        return Collection::create($this->table, $rows);
    }


    /**
     * Delete rows matching the query
     *
     * @return int Number of affected rows
     */
    public function delete()
    {
        list($queryString, $params) = $this->getQueryString();
        $query = "DELETE FROM `{$this->table}` ".$queryString;
        self::$lastExecutedQuery = $query;

        return Rhapsody::getConnection()->executeUpdate($query, $params);
    }


    /**
     * Update the table
     *
     * @param  array  $values
     *
     * @return int Number of affected rows
     */
    public function update(array $values)
    {
        $updateString = null;
        $updateParams = array();
        foreach ($values as $column => $value) {
            $column = Inflector::tableize($column);
            $updateString .= $updateString ? " `$column` = ? " : ", `$column` = ?";
            $updateParams[] = $value;
        }

        list($queryString, $params) = $this->getQueryString();
        $query = "UPDATE `{$this->table}` SET $updateString ".$queryString;
        $params = array_merge($updateParams, $params);
        self::$lastExecutedQuery = $query;

        return Rhapsody::getConnection()->executeUpdate($query, $params);
    }


    /**
     * Get the query string and query parameters
     *
     * @return array($string, $params)
     */
    private function getQueryString()
    {
        list($where, $params) = $this->getWhereString();
        $order = $this->getOrderByString();
        $offset = $this->offset === null ? '' : $this->offset.', ';
        $limit = $this->limit !== null ? " LIMIT $offset {$this->limit} " : '';

        $query = ' '.$where.$order.$limit.' ';

        return array($query, $params);
    }

    /**
     * Get the WHERE string and params
     *
     * @return array
     */
    private function getWhereString()
    {
        $where = '';
        $params = array();

        if (! empty($this->filters)) {
            $where = ' WHERE 1 ';
            foreach ($this->filters as $filter) {
                $where .= ' AND '.$filter['sql'];
                $params = array_merge($params, $filter['params']);
            }
            $where .= ' ';
        }

        return array($where, $params);
    }


    /**
     * Get the order by string
     *
     * @return string
     */
    private function getOrderByString()
    {
        $orderBy = '';

        if (! empty($this->orderByColumns)) {
            $orderBy = ' ORDER BY ';

            foreach ($this->orderByColumns as $orderByColumn) {
                $orderBy .= $orderByColumn['column'].' '.$orderByColumn['type'].' ';
            }
        }

        return $orderBy;
    }


    /**
     * Magic methods
     */
    public function __call($name, $arguments)
    {
        if (strpos($name, 'filterBy') === 0) {
            $column = str_replace('filterBy', '', $name);
            $comparison = isset($arguments[1]) ? $arguments[1] : '=';

            return $this->filterBy($column, $arguments[0], $comparison);
        }

        if (strpos($name, 'orderBy') === 0) {
            $column = str_replace('orderBy', '', $name);

            return $this->orderBy($column, $arguments[0]);
        }
    }
}
