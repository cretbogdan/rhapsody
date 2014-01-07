<?php

namespace Rhapsody;

use Doctrine\Common\Util\Inflector;
use Rhapsody\Query\Filter;
use Rhapsody\Query\ColumnFilter;
use Rhapsody\Query\FilterCollection;
use Rhapsody\Query\FilterUtils;

class Query
{
    protected $table;
    private $queryBuilder;
    private $limit;
    private $offset;
    private $filters;
    private $orderByColumns = array();
    private $virtualColumns = array();

    protected function __construct($table)
    {
        if ($table) {
            $table = Inflector::tableize($table);
            $this->table = $table;
            $this->filters = new FilterCollection();
            $this->queryBuilder = Rhapsody::getConnection()->createQueryBuilder();
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
    public function orderBy($column, $type = null)
    {
        $column = Inflector::tableize($column);
        $this->queryBuilder->addOrderBy($column, $type);

        return $this;
    }

    public function withColumn($sql, $name)
    {
        $this->virtualColumns[$name] = $sql;

        return $this;
    }

    public function addSelect($select)
    {
        $this->queryBuilder->addSelect($select);

        return $this;
    }

    public function groupBy($groupBy)
    {
        $this->queryBuilder->addGroupBy($groupBy);

        return $this;
    }


    /**
     * Add a having condition
     *
     * @param  string $having
     *
     * @return Query
     */
    public function having($having)
    {
        $this->queryBuilder->andHaving($having);

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
        $filter = new ColumnFilter($column, $value, $comparison);
        $this->filters->add($filter);

        $value = $filter->getValue() ? $this->queryBuilder->createNamedParameter($filter->getValue()) : '';

        $this->queryBuilder->andWhere($filter->getColumn()." ".$filter->getComparison()." ".$value);

        return $this;
    }


    /**
     * Add a where condition
     *
     * @param  string $sql
     * @param  string|array $params
     *
     * @return Query
     */
    public function where($sql, $params = array())
    {
        if (! $sql) {
            return $this;
        }

        $this->queryBuilder->andWhere($sql);

        $params = (array) $params;
        foreach ($params as $key => $param) {
            if (is_int($key)) {
                $this->queryBuilder->createPositionalParameter($param);
            } else {
                $this->queryBuilder->setParameter($key, $param);
            }
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
        $this->queryBuilder->setFirstResult($offset);

        return $this;
    }

    /**
     * Set the limit for current query
     *
     * @param  int $limit
     */
    public function limit($limit)
    {
        $this->queryBuilder->setMaxResults($limit);

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
        $stmt = $this->getSelectBuilder()->execute();
        $data = $stmt->fetch();

        $object = null;

        if ($data) {
            $cache = Rhapsody::getObjectCache();
            $object = $cache->fetchObject($data['id'], $this->table);
            $virtualColumns = array_keys($this->virtualColumns);

            if (! $object) {
                $object = Rhapsody::create($this->table);
                $object->addVirtualColumns($virtualColumns);
                $object->fromArray($data);
                $cache->saveObject($object);
            }

            if (! empty($virtualColumns) && ! $object->hasVirtualColumns($virtualColumns)) {
                $object->addVirtualColumns($virtualColumns);
                $object->populateVirtualColumns($data);
            }
        }

        return $object;
    }

    /**
     * Find a single record or create a new one
     *
     * @param boolean $save Save newly created object
     *
     * @return Object|null
     */
    public function findOneOrCreate()
    {
        $object = $this->findOne();

        if (! $object) {
            $object = Rhapsody::create($this->table);

            foreach ($this->filters as $filter) {
                $object->set($filter->getColumn(), $filter->getValue());
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
        $stmt = $this->getSelectBuilder()->execute();
        $collection = new Collection($this->table);
        $collection->addVirtualColumns(array_keys($this->virtualColumns));
        $collection->fromArray($stmt->fetchAll());

        return $collection;
    }

    /**
     * Count all records matching the query conditions
     */
    public function count()
    {
        $this->limit = 1;

        return (int) $this->getCountBuilder()->execute()->fetchColumn();
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
        return $this->getTableBuilder()->delete($this->table, '')->execute();
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
        foreach ($values as $column => $value) {
            $this->queryBuilder->set($column, $value);
        }

        return $this->getTableBuilder()->update($this->table, '')->execute();
    }

    private function getSelectBuilder()
    {
        $builder = $this->getTableBuilder()->addSelect($this->table.'.*');

        foreach ($this->virtualColumns as $name => $sql) {
            $builder->addSelect($sql." AS ".$name);
        }

        return $builder;//->execute();
    }

    private function getCountBuilder()
    {
        return $this->getTableBuilder()->select('COUNT(*)'); //->execute();
    }

    private function getTableBuilder()
    {
        return $this->queryBuilder->from($this->table, $this->table);
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
