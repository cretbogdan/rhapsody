<?php

namespace Rhapsody\Query;

use Rhapsody\Collection;

class FilterCollection extends Collection
{
    public function getFilters()
    {
        return $this->getElements();
    }

    public function getSql()
    {
        $sql = '';

        if (! $this->isEmpty()) {
            $sql = '1';

            foreach ($this->getFilters() as $filter) {
                $sql .= ' AND ('.$filter->getSql().')';
            }

            $sql .= ' ';
        }

        return $sql;
    }

    public function getParameters()
    {
        $parameters = array();

        if (! $this->isEmpty()) {
            foreach ($this->getFilters() as $filter) {
                $parameters = array_merge($parameters, $filter->getParameters());
            }
        }

        return $parameters;
    }
}
