<?php

declare(strict_types=1);

namespace App\Entity\Framework;

interface ChangedAtInterface
{
    public function getUpdatedAt(): \DateTimeInterface;

    public function getChangedAt(): \DateTimeInterface;
}
