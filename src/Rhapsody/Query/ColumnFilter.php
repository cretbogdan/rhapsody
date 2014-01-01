<?php

namespace Rhapsody\Query;

use Doctrine\Common\Util\Inflector;

class ColumnFilter
{
    private $column;
    private $value;
    private $comparison;

    public function __construct($column, $value, $comparison = '=')
    {
        $column = Inflector::tableize($column);
        $value = static::cleanValue($value, $comparison);

        if (null === $value && '=' === $comparison) {
            $comparison = ' is null ';
            $value = '';
        }

        $this->column = $column;
        $this->value = $value;
        $this->comparison = $comparison;
    }

    public function getColumn()
    {
        return $this->column;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function getComparison()
    {
        return $this->comparison;
    }

    public static function cleanValue($value, $comparison)
    {
        if (is_bool($value)) {
            $value = (int) $value;
        }

        if ('in' == strtolower($comparison) && is_array($value)) {
            $value = implode(',', $value);
        }

        return $value;
    }
 }
