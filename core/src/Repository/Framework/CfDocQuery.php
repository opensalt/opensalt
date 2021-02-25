<?php

namespace App\Repository\Framework;

class CfDocQuery
{
    public int $limit = 0;

    public int $offset = 0;

    public ?string $sort = null;

    public string $orderBy = 'ASC';

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
