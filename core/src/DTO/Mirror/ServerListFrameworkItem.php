<?php

namespace App\DTO\Mirror;

class ServerListFrameworkItem
{
    public function __construct(
        public int $serverId,
        public string $status,
        public bool $included,
    ) {
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function isInclude(): bool
    {
        return $this->included;
    }
}
