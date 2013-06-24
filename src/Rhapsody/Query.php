<?php

namespace Rhapsody;

class Query
{
    private $table;
    private $limit;
    private $filters = array();

    public function __construct($table)
    {
        $this->table = $table;
    }

    public static function create($table = null)
    {
        return new static($table);
    }

    public function filterBy($column, $value, $comparison = '=')
    {
        return $this->where("$column $comparison ?", array($value));
    }

    public function where($sql, array $params = array())
    {
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
        $query = "SELECT * FROM `{$this->table}` $where LIMIT 1";
        $data = Rhapsody::getConnection()->fetchAssoc($query, $params);

        return Rhapsody::createObject($this->table, $data);
    }

    public function find()
    {
        list($where, $params) = $this->getWhere();
        $limit = $limit !== null ? " LIMIT $limit " : '';
        $query = "SELECT * FROM `{$this->table}` $where $limit";

        $rows = Rhapsody::getConnection()->fetchAll($query, $params);
        $collection = new Collection($this->table);
        $collection->fromArray($rows);

        return $collection;
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
        }

        return array($where, $params);
    }
}
