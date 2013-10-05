<?php

namespace Rhapsody;

class Pager
{
    private $query;
    private $page;
    private $maxPerPage;
    private $totalRows;
    private $results;

    public function __construct($query, $page, $maxPerPage)
    {
        $this->query = $query;
        $this->page = $page;
        $this->maxPerPage = $maxPerPage;

        $countQuery = clone $query;
        $this->totalRows = $countQuery->count();

        $offset = ($page - 1) * $maxPerPage;
        $this->results = $query->offset($offset)->limit($maxPerPage)->find();
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
        return ceil($this->totalRows / $this->maxPerPage);
    }

    public function haveToPaginate()
    {
        return $this->totalRows > $this->maxPerPage;
    }

    public function getLinks($nb_links = 5)
    {
        $links = array();
        $tmp     = $this->page - floor($nb_links / 2);
        $check = $this->getLastPage() - $nb_links + 1;
        $limit = ($check > 0) ? $check : 1;
        $begin = ($tmp > 0) ? (($tmp > $limit) ? $limit : $tmp) : 1;

        $i = (int) $begin;
        while (($i < $begin + $nb_links) && ($i <= $this->getLastPage())) {
            $links[] = $i++;
        }

        return $links;
    }

    public function getResults()
    {
        return $this->results;
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
