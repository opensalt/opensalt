<?php

namespace CftfBundle\Entity;

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
