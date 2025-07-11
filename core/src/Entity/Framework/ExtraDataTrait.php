<?php

declare(strict_types=1);

namespace App\Entity\Framework;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

trait ExtraDataTrait
{
    #[ORM\Column(name: 'extra', type: Types::JSON, nullable: true)]
    private ?array $extra = null;

    public function getExtra(): array
    {
        return $this->extra ?? [];
    }

    public function setExtra(?array $extra): static
    {
        $this->extra = $extra;

        return $this;
    }

    public function getExtraProperty(string $property): mixed
    {
        return $this->extra[$property] ?? null;
    }

    public function setExtraProperty(string $property, mixed $value): static
    {
        if (null === $this->extra && null === $value) {
            return $this;
        }

        if (null === $value) {
            unset($this->extra[$property]); // @phpstan-ignore unset.possiblyHookedProperty
        } else {
            $this->extra[$property] = $value;
        }

        return $this;
    }
}
