<?php

namespace Helper;

class Remember extends \Codeception\Module
{
    protected $remembered = [];

    public function remember($key, $value)
    {
        $this->remembered[$key] = $value;
    }

    public function getRememberedString($key)
    {
        return $this->remembered[$key] ?? '';
    }
}
