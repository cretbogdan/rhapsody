<?php

namespace Rhapsody\Query;

use Doctrine\Common\Util\Inflector;

class ColumnFilter
{
    private $column;
    private $value;

    public function __construct($column, $value)
    {
        $column = Inflector::tableize($column);
        $value = FilterUtils::cleanValue($value, '=');
        $this->column = $column;
        $this->value = $value;
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
