<?php

namespace App\Repository\Framework;

class CfDocQuery
{
    /** @var int */
    public $limit;

    /** @var int */
    public $offset;

    /** @var string */
    public $sort;

    /** @var string */
    public $orderBy = 'ASC';

    public function getLimit(): ?int
    {
        $limit = (int) $this->limit;

        return ($limit > 0) ? $limit : null;
    }

    public function getOffset(): int
    {
        $offset = (int) $this->offset;

        return ($offset > 0) ? $offset : 0;
    }

    public function getSort(): ?string
    {
        return $this->sort;
    }

    public function getOrderBy(): string
    {
        return ('ASC' === strtoupper($this->orderBy)) ? 'ASC' : 'DESC';
    }
}
