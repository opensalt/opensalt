<?php

namespace CftfBundle\Entity;

/**
 * Interface CaseApiInterface
 *
 * Identifies objects that are exposed via the CASE API
 */
interface CaseApiInterface
{
    /**
     * @return null|string
     */
    public function getIdentifier(): ?string;

    /**
     * @return \DateTime|null
     */
    public function getUpdatedAt(): ?\DateTime;
}
