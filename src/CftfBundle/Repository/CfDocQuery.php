<?php

namespace CftfBundle\Repository;

class CfDocQuery
{
    /** @var  int */
    public $limit;

    /** @var  int */
    public $offset;

    /**
     * @return int|null
     */
    public function getLimit(): ?int
    {
        $limit = (int) $this->limit;

        return ($limit > 0) ? $limit : null;
    }

    /**
     * @return int|null
     */
    public function getOffset(): ?int
    {
        $offset = (int) $this->offset;

        return ($offset > 0) ? $offset : null;
    }
}
