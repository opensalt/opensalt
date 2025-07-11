<?php

declare(strict_types=1);

namespace App\Entity\Framework;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

trait ChangedAtTrait
{
    #[ORM\Column(name: 'changed_at', type: 'datetime', precision: 6)]
    #[Gedmo\Timestampable(on: 'update')]
    private \DateTimeInterface $changedAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime', precision: 6)]
    #[Gedmo\Timestampable(on: 'update')]
    private \DateTimeInterface $updatedAt;

    public function setChangedAt(\DateTimeInterface $changedAt): static
    {
        $this->changedAt = $changedAt;

        return $this;
    }

    public function getChangedAt(): \DateTimeInterface
    {
        return $this->changedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }
}
