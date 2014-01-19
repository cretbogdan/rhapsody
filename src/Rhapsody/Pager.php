<?php

namespace Rhapsody;

class Pager
{
    private $query;
    private $page;
    private $maxPerPage;
    private $nbResults;
    private $results;

    public function __construct($query, $page, $maxPerPage)
    {
        $this->page = $page;
        $this->maxPerPage = $maxPerPage;

        $countQuery = clone $query;
        $this->nbResults = $countQuery->count();

        $offset = ($page - 1) * $maxPerPage;
        $this->results = $query->offset($offset)->limit($maxPerPage)->find();
    }

    public function getFirstPage()
    {
        return 1;
    }

    public function getPreviousPage()
    {
        return $this->getPage() > 1 ? $this->getPage() - 1 : null;
    }

    public function getNextPage()
    {
        return $this->getPage() < $this->getLastPage() ? $this->getPage() + 1 : null;
    }

    public function getLastPage()
    {
        return ceil($this->nbResults / $this->maxPerPage);
    }

    public function haveToPaginate()
    {
        return $this->nbResults > $this->maxPerPage;
    }

    public function getLinks($nbLinks = 5)
    {
        $links = array();
        $tmp   = $this->page - floor($nbLinks / 2);
        $check = $this->getLastPage() - $nbLinks + 1;
        $limit = ($check > 0) ? $check : 1;
        $begin = ($tmp > 0) ? (($tmp > $limit) ? $limit : $tmp) : 1;

        $i = (int) $begin;
        while (($i < $begin + $nbLinks) && ($i <= $this->getLastPage())) {
            $links[] = $i++;
        }

        return $links;
    }

    public function getResults()
    {
        return $this->results;
    }

    public function getNbResults()
    {
        return $this->nbResults;
    }

    public function getPage()
    {
        return $this->page;
    }

    public function getMaxPerPage()
    {
        return $this->maxPerPage;
    }
}
