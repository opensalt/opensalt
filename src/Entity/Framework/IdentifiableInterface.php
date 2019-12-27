<?php

namespace App\Entity\Framework;

interface IdentifiableInterface
{
    /**
     * @return string
     */
    public function getIdentifier(): string;

    /**
     * @return string
     */
    public function getUri(): string;
}
