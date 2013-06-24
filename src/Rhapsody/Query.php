<?php

namespace Rhapsody;

class Query
{
    protected $table;
    private $limit;
    private $filters = array();
    private $orders = array();
    private $queryBuilder;

    public function __construct($table = null)
    {
        if ($table) {
            $this->table = $table;
        }
    }

    public static function create($table = null)
    {
        return new static($table);
    }

    public function orderBy($column, $type = 'asc')
    {
        $this->orders[] = array('column' => $column, 'type' => $type);

        return $this;
    }

    public function filterBy($column, $value, $comparison = '=')
    {
        return $this->where("$column $comparison ?", array($value));
    }

    public function where($sql, $params = null)
    {
        $params = is_array($params) ? $params : array($params);
        $this->filters[] = array('sql' => $sql, 'params' => $params);

        return $this;
    }

    public function limit($limit)
    {
        $this->limit = $limit;
    }

    public function findOne()
    {
        list($where, $params) = $this->getWhere();
        $order = $this->getOrderByString();
        $query = "SELECT * FROM `{$this->table}` $where $order LIMIT 1";
        $data = Rhapsody::getConnection()->fetchAssoc($query, $params);

        return Rhapsody::createObject($this->table, $data);
    }

    public function find()
    {
        list($where, $params) = $this->getWhere();
        $limit = $this->limit !== null ? " LIMIT $limit " : '';
        $order = $this->getOrderByString();
        $query = "SELECT * FROM `{$this->table}` $where $order $limit";

        $rows = Rhapsody::getConnection()->fetchAll($query, $params);
        $collection = new Collection($this->table);
        $collection->fromArray($rows);

        return $collection;
    }

    public function delete()
    {
        list($where, $params) = $this->getWhere();
        $limit = $limit !== null ? " LIMIT $limit " : '';
        $order = $this->getOrderByString();
        $query = "DELETE FROM `{$this->table}` $where $order $limit";

        return Rhapsody::getConnection()->executeUpdate($query, $params);
    }


    public function update(array $values)
    {
        list($where, $params) = $this->getWhere();
        $limit = $limit !== null ? " LIMIT $limit " : '';
        $order = $this->getOrderByString();

        $updateString = null;
        $updateParams = array();
        foreach ($values as $column => $value) {
            $updateString .= $updateString ? " $column = ? " : ", $column = ?";
            $updateParams[] = $value;
        }

        $query = "UPDATE `{$this->table}` SET $updateString $where $order $limit";
        $params = array_merge($updateParams, $params);

        return Rhapsody::getConnection()->executeUpdate($query, $params);
    }

    /**
     * Create the WHERE statement
     *
     * @return array
     */
    private function getWhere()
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


    private function getOrderByString()
    {
        $orderBy = '';

        if (! empty($this->orders)) {
            $orderBy = ' ORDER BY ';

            foreach ($this->orders as $order) {
                $orderBy .= $order['column'].' '.$order['type'].' ';
            }
        }

        return $orderBy;
    }
}
