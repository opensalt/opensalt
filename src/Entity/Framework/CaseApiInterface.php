<?php

namespace App\Entity\Framework;

/**
 * Interface CaseApiInterface
 *
 * Identifies objects that are exposed via the CASE API
 */
interface CaseApiInterface extends IdentifiableInterface
{
    /**
     * @return \DateTimeInterface
     */
    public function getUpdatedAt(): \DateTimeInterface;
}
