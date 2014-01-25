<?php

namespace Rhapsody;

use Closure;
use Doctrine\Common\Util\Inflector;
use Rhapsody\Query\Filter;
use Rhapsody\Query\ColumnFilter;
use Rhapsody\Query\FilterCollection;
use Rhapsody\Query\FilterUtils;

class Query
{
    protected $table;
    protected $alias;
    private $queryBuilder;
    private $limit;
    private $offset;
    private $filters;
    private $orderByColumns = array();
    private $virtualColumns = array();
    private $rawSql;
    private $rawParams;

    protected function __construct($table, $alias = null)
    {
        if (! $table) {
            throw new \RhapsodyException("No table provided for query!");
        }

        $this->table = Inflector::tableize($table);
        $this->alias = $alias ? $alias : $this->table;

        $this->filters = new FilterCollection();
        $this->queryBuilder = Rhapsody::getConnection()
            ->createQueryBuilder()
            ->from($this->table, $this->alias)
            ->addSelect($this->alias.'.*');
    }

    public static function create($table = null, $alias = null)
    {
        $class = Rhapsody::getQueryClass($table);

        return new $class($table, $alias);
    }

    public function raw($sql, array $params = array())
    {
        $this->rawSql = $sql;
        $this->rawParams = $params;

        return $this;
    }

    public function customJoin($fromAlias, $joinTable, $joinTableAlias, $condition)
    {
        return $this->customInnerJoin($fromAlias, $joinTable, $joinTableAlias, $condition);
    }

    public function customInnerJoin($fromAlias, $joinTable, $joinTableAlias, $condition)
    {
        $this->queryBuilder->innerJoin($fromAlias, $joinTable, $joinTableAlias, $condition);

        return $this;
    }

    /**
     * Shortcut for innerJoin
     *
     * @see Query::innerJoin
     */
    public function join($foreignTableName, $foreignTableAlias = null, $condition = null)
    {
        return $this->innerJoin($foreignTableName, $foreignTableAlias, $condition);
    }

    /**
     * Inner join a foreign table
     *
     * @param  string $foreignTableName
     * @param  string $foreignTableAlias
     * @param  string $condition
     *
     * @return Query
     */
    public function innerJoin($foreignTableName, $foreignTableAlias = null, $condition = null)
    {
        return $this->addJoin('inner', $foreignTableName, $foreignTableAlias, $condition);
    }

    /**
     * Left join a foreign table
     *
     * @param  string $foreignTableName
     * @param  string $foreignTableAlias
     * @param  string $condition
     *
     * @return Query
     */
    public function leftJoin($foreignTableName, $foreignTableAlias = null, $condition = null)
    {
        return $this->addJoin('left', $foreignTableName, $foreignTableAlias, $condition);
    }

    /**
     * Right join a foreign table
     *
     * @param  string $foreignTableName
     * @param  string $foreignTableAlias
     * @param  string $condition
     *
     * @return Query
     */
    public function rightJoin($foreignTableName, $foreignTableAlias = null, $condition = null)
    {
        return $this->addJoin('right', $foreignTableName, $foreignTableAlias, $condition);
    }

    /**
     * Add a join
     *
     * @param string $type
     * @param string $foreignTableName
     * @param string $foreignTableAlias
     * @param string $condition
     *
     * @return Query
     *
     * @throws RhapsodyException    If unknown join type
     */
    private function addJoin($type = 'inner', $foreignTableName, $foreignTableAlias = null, $condition = null)
    {
        $foreignTableName = Inflector::tableize($foreignTableName);
        $foreignTableAlias = $foreignTableAlias ?: $foreignTableName;

        if (! $condition) {
            $condition = $this->guessJoinCondition($foreignTableName, $foreignTableAlias);
        }

        if ('inner' == $type) {
            $this->queryBuilder->innerJoin($this->alias, $foreignTableName, $foreignTableAlias, $condition);
        } elseif ('left' == $type) {
            $this->queryBuilder->leftJoin($this->alias, $foreignTableName, $foreignTableAlias, $condition);
        } elseif ('right' == $type) {
            $this->queryBuilder->rightJoin($this->alias, $foreignTableName, $foreignTableAlias, $condition);
        } else {
            throw new RhapsodyException("Unknown join type $type");
        }

        return $this;
    }

    /**
     * Guess the join condition with a foreign table
     *
     * @param  string $foreignTableName
     * @param  string $foreignTableAlias
     *
     * @return string
     *
     * @throws RhapsodyException    If condition cannot be guessed
     */
    protected function guessJoinCondition($foreignTableName, $foreignTableAlias)
    {
        if (Rhapsody::getTableManager()->getTable($foreignTableName)->hasColumn($this->table.'_id')) {
            return $this->alias.".id = ".$foreignTableAlias.".".$this->table.'_id';
        }

        if ($this->getTableObject()->hasColumn($foreignTableName.'_id')) {
            return $this->alias.".".$foreignTableName."_id = ".$foreignTableAlias.".id";
        }

        throw new \RhapsodyException("Cannot guess join condition for $this->table and $foreignTableName");
    }
/*
     * @param string $fromAlias The alias that points to a from clause
     * @param string $join The table name to join
     * @param string $alias The alias of the join table
     * @param string $condition The condition for the join
     * @return QueryBuilder This QueryBuilder instance.
    public function innerJoin($fromAlias, $join, $alias, $condition = null)
    {
 */
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

    public function orderByRandom()
    {
        $this->queryBuilder->addOrderBy('RAND()');

        return $this;
    }

    public function orderByRand()
    {
        return $this->orderByRandom();
    }

    /**
     * Add a virtual column
     *
     * @param  string $sql
     * @param  string $name
     *
     * @return Query
     */
    public function withColumn($sql, $name)
    {
        $this->virtualColumns[$name] = $sql;
        $this->queryBuilder->addSelect($sql." AS ".$name);

        return $this;
    }

    /**
     * Add a select statement
     *
     * e.g. COUNT(*) AS total
     *
     * @param string $select
     *
     * @return Query
     */
    public function addSelect($select)
    {
        $this->queryBuilder->addSelect($select);

        return $this;
    }

    /**
     * Add a group by
     *
     * @param  string $groupBy
     *
     * @return Query
     */
    public function groupBy($groupBy)
    {
        $tableizedColumn = Inflector::tableize($groupBy);

        if (Rhapsody::getTableManager()->hasColumn($this->table, $tableizedColumn)) {
            $groupBy = $tableizedColumn;
        }

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

        $value = $filter->getValue() ? "(".$this->queryBuilder->createNamedParameter($filter->getValue()).")" : '';
        $this->queryBuilder->andWhere($this->alias.'.'.$filter->getColumn()." ".$filter->getComparison()." ".$value);

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
     * @return Object|null
     */
    public function findOne()
    {
        if ($this->rawSql) {
            $data = Rhapsody::getConnection()->fetchArray($this->rawSql, $this->rawParams);
        } else {
            $this->limit(1);
            $stmt = $this->queryBuilder->execute();
            $data = $stmt->fetch();
        }

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
        if ($this->rawSql) {
            $rows = Rhapsody::getConnection()->fetchAll($this->rawSql, $this->rawParams);
        } else {
            $stmt = $this->queryBuilder->execute();
            $rows = $stmt->fetchAll();
        }

        $collection = Rhapsody::createCollection($this->table);
        $collection->addVirtualColumns(array_keys($this->virtualColumns));
        $collection->fromArray($rows);

        return $collection;
    }

    /**
     * @see chunk
     */
    public function chunkWithoutOffsetIncrease($size, Closure $callback)
    {
        $this->chunk($size, $callback, false);
    }

    /**
     * Chunk query results
     *
     * @param  int     $size
     * @param  Closure $callback
     * @param  boolean $increaseOffset  If set to FALSE the offset for query will not be increased.
     *                                  Useful when you alter the results and thus alter the query results
     *
     * @return Collection
     */
    public function chunk($size, Closure $callback, $increaseOffset = true)
    {
        $chunkNumber = 1;
        $offset = 0;

        do {
            $collection = $this->offset($offset)->limit($size)->find();
            $callback($collection, $chunkNumber);

            $chunkNumber++;
            $offset += $increaseOffset ? $size : 0;
        } while (! $collection->isEmpty());
    }

    /**
     * Count all records matching the query conditions
     */
    public function count()
    {
        if ($this->rawSql) {
            $result = (int) Rhapsody::getConnection()->fetchColumn("SELECT COUNT(*) FROM (".$this->rawSql.") count", $this->rawParams);
        } else {
            $result = (int) $this->queryBuilder->select('COUNT(*)')->execute()->fetchColumn();
        }

        return $result;
    }

    public function __clone()
    {
        $this->queryBuilder = clone $this->queryBuilder;
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
        return $this->queryBuilder->delete($this->table, '')->execute();
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

        return $this->queryBuilder->update($this->table, '')->execute();
    }

    public function getTableObject()
    {
        return Rhapsody::getTableManager()->getTable($this->table);
    }

    /**
     * Magic methods
     */
    public function __call($name, $arguments)
    {
        if (strpos($name, 'filterBy') === 0) {
            $column = str_replace('filterBy', '', $name);
            $value = isset($arguments[0]) ? $arguments[0] : null;
            $comparison = isset($arguments[1]) ? $arguments[1] : '=';

            return $this->filterBy($column, $value, $comparison);
        }

        if (strpos($name, 'orderBy') === 0) {
            $column = str_replace('orderBy', '', $name);
            $type = isset($arguments[0]) ? $arguments[0] : 'asc';

            return $this->orderBy($column, $type);
        }

        if (strpos($name, 'groupBy') === 0) {
            $column = str_replace('groupBy', '', $name);

            return $this->groupBy($column);
        }

        if (strpos($name, 'join') === 0) {
            $table = str_replace('join', '', $name);
            array_unshift($arguments, $table);

            return call_user_func_array(array($this, 'join'), $arguments);
        }

        if (strpos($name, 'innerJoin') === 0) {
            $table = str_replace('innerJoin', '', $name);
            array_unshift($arguments, $table);

            return call_user_func_array(array($this, 'innerJoin'), $arguments);
        }

        if (strpos($name, 'leftJoin') === 0) {
            $table = str_replace('leftJoin', '', $name);
            array_unshift($arguments, $table);

            return call_user_func_array(array($this, 'leftJoin'), $arguments);
        }

        if (strpos($name, 'rightJoin') === 0) {
            $table = str_replace('rightJoin', '', $name);
            array_unshift($arguments, $table);

            return call_user_func_array(array($this, 'rightJoin'), $arguments);
        }

        throw new \BadMethodCallException("Method $name does not exist!");
    }
}
