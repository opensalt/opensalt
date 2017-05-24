<?php

namespace CftfBundle\Entity;

/**
 * Interface IdentifiableInterface
 */
interface IdentifiableInterface
{
    /**
     * @return null|string
     */
    public function getIdentifier(): ?string;

    /**
     * @return null|string
     */
    public function getUri(): ?string;
}
