<?php

namespace Rhapsody\Query;

use Doctrine\Common\Util\Inflector;

class ColumnFilter extends Filter
{
    private $column;
    private $value;

    public function __construct($column, $value, $comparison = '=')
    {
        $column = Inflector::tableize($column);
        $value = FilterUtils::cleanValue($value, $comparison);
        $this->column = $column;
        $this->value = $value;

        parent::__construct("$column $comparison ?", $value);
    }

    public function getColumn()
    {
        return $this->column;
    }

    public function getValue()
    {
        return $this->value;
    }
 }
