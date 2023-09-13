<?php

namespace App\DTO\Mirror;

class ServerListItem
{
    public function __construct(
        public int $id,
        public string $url,
        public array $frameworks = [],
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getFrameworks(): array
    {
        return $this->frameworks;
    }
}
