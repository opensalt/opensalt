<?php

namespace App\Entity\Framework;

interface IdentifiableInterface
{
    public function getIdentifier(): string;

    public function getUri(): string;

    public function getId(): ?int;

    public function getUpdatedAt(): \DateTimeInterface;

    public function getChangedAt(): \DateTimeInterface;
}
