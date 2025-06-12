<?php

declare(strict_types=1);

namespace App\Entity\Framework;

use Ramsey\Uuid\Uuid;

trait CloneIdentifiableTrait
{
    public function __clone()
    {
        // Clear values for new item
        $this->id = null;

        // Generate a new identifier
        $identifier = Uuid::uuid1()->toString();
        $this->identifier = $identifier;

        $this->uri = 'local:'.$this->identifier;

        // Set last change/update to now
        $this->updatedAt = new \DateTimeImmutable();
        $this->changedAt = $this->updatedAt;
    }
}
