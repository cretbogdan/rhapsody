<?php

namespace Rhapsody\Logger;

use Doctrine\DBAL\Logging\DebugStack as BaseDebugStack;

class EchoDebugStack extends BaseDebugStack
{
    public function startQuery($sql, array $params = null, array $types = null)
    {
        parent::startQuery($sql, $params, $types);

        if ($this->enabled) {
            $query = $this->queries[$this->currentQuery];
            echo "Starting query: ".$query['sql']." with \n".print_r($query['params'], true)."\n";
        }
    }

    public function stopQuery()
    {
        parent::stopQuery();

        if ($this->enabled) {
            $query = $this->queries[$this->currentQuery];
            echo "time: ".$query['executionMS']."\n";
        }
    }
}

