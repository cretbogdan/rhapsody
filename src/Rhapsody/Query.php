<?php

namespace Rhapsody;

use Doctrine\Common\Util\Inflector;
use Rhapsody\Query\Filter;
use Rhapsody\Query\ColumnFilter;
use Rhapsody\Query\FilterCollection;
use Rhapsody\Query\FilterUtils;

class Query
{
    private static $queryStack = array();
    protected $table;
    private $limit;
    private $offset;
    private $filters;
    private $orderByColumns = array();

    protected function __construct($table = null)
    {
        if ($table) {
            $table = Inflector::tableize($table);
            $this->table = $table;
            $this->filters = new FilterCollection();
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
     * @param  mixed  $value
     * @param  string $comparison
     *
     * @return Query
     */
    public function filterBy($column, $value, $comparison = '=')
    {
        $this->filters->append(new ColumnFilter($column, $value, $comparison));

        return $this;
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
            $filter = new Filter($sql, $params);
            $this->filters->append($filter);
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
        $data = Rhapsody::getConnection()->fetchAssoc($query, $params);
        $this->pushQueryStack($query, $params);

        return $data ? Rhapsody::create($this->table, $data) : null;
    }

    /**
     * Find a single record or create a new one
     *
     * @param boolean $save Save newly created object
     *
     * @return Object/null
     */
    public function findOneOrCreate($save = false)
    {
        $object = $this->findOne();

        if (! $object) {
            $object = Rhapsody::create($this->table);

            foreach ($this->filters as $filter) {
                if ($filter instanceof ColumnFilter) {
                    $object->set($filter->getColumn(), $filter->getValue());
                }
            }

            if ($save) {
                $object->save();
            }
        }

        return $object;
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
        $this->pushQueryStack($query, $params);

        return Collection::create($this->table, $rows);
    }

    /**
     * Count all records matching the query conditions
     */
    public function count()
    {
        list($queryString, $params) = $this->getQueryString();
        $query = "SELECT COUNT(*) FROM `{$this->table}` ".$queryString;
        $count = Rhapsody::getConnection()->fetchColumn($query, $params);
        $this->pushQueryStack($query, $params);

        return $count;
    }


    /**
     * Create a pager
     *
     * @param  integer  $page
     * @param  integer  $maxPerPage
     *
     * @return Pager
     */
    public function paginate($page, $maxPerPage = 20)
    {
        return new Pager($this, $page, $maxPerPage);
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
        $this->pushQueryStack($query, $params);

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
        $this->pushQueryStack($query, $params);

        return Rhapsody::getConnection()->executeUpdate($query, $params);
    }

    /**
     * Get last executed query
     *
     * @return string
     */
    public static function getLastExecutedQuery()
    {
        $query = end(array_values(static::$queryStack));

        if (false !== $query) {
            list ($sql, $params) = $query;
            $query = $sql.print_r($params, true);
        }

        return $query;
    }

    /**
     * Get the query string and query parameters
     *
     * @return array($string, $params)
     */
    private function getQueryString()
    {
        $offset = $this->offset === null ? '' : $this->offset.', ';
        $limit = $this->limit !== null ? " LIMIT $offset {$this->limit} " : '';

        $query = ' WHERE '.$this->filters->getSql().$this->getOrderByString().$limit.' ';

        return array($query, $this->filters->getParameters());
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


    private function pushQueryStack($sql, $params)
    {
        static::$queryStack[] = array($sql, $params);
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

        throw new \BadMethodCallException("Method $name does not exist!");
    }
}
