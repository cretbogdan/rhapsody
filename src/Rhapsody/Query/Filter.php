<?php

namespace Rhapsody\Query;

use Doctrine\Common\Util\Inflector;

class Filter
{
    private $sql;
    private $parameters = array();
    private $isExact = false;

    public function __construct($sql, $parameters = null)
    {
        if (null === $parameters) {
            $parameters = array();
        } elseif (! is_array($parameters)) {
            $parameters = $parameters === '' ? array() : array($parameters);
        }

        if (! is_array($parameters)) {
            throw new \InvalidArgumentException("Second argument passed to Rhapsody\Query\Filter::__construct() must be an array, ".gettype($parameters)." given.");
        }

        if (1 === count($parameters) && false !== strpos(reset($parameters), '=')) {
            $this->isExact = true;
        }

        $this->sql = (string) $sql;
        $this->parameters = $parameters;
    }

    public function getSql()
    {
        return $this->sql;
    }

    public function getParameters()
    {
        return $this->parameters;
    }

    public function isExact()
    {
        return $this->isExact;
    }
}
