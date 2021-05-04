<?php

namespace App\Entity\Framework;

interface IdentifiableInterface
{
    public function getIdentifier(): string;


    public function getUri(): string;
}
