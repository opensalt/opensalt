<?php

namespace App\Entity\Framework;

/**
 * Interface IdentifiableInterface
 */
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
