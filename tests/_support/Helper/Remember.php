<?php

namespace Helper;

class Remember extends \Codeception\Module
{
    protected $remembered = [];

    public function remember($key, $value): void
    {
        $this->remembered[$key] = $value;
    }

    public function getRememberedString($key, string $default = ''): string
    {
        return $this->remembered[$key] ?? $default;
    }
}
