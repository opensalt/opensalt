<?php

declare(strict_types=1);

namespace App\Entity\Framework;

use Doctrine\ORM\Mapping as ORM;

trait ExtensionTrait
{
    #[ORM\Column(name: 'ext', type: 'json', nullable: true)]
    protected ?array $extensions = null;

    public function getExtensions(): array
    {
        return $this->extensions ?? [];
    }

    public function setExtensions(?array $extensions): static
    {
        if ([] === $extensions) {
            $this->extensions = null;

            return $this;
        }

        $this->extensions = $extensions;

        return $this;
    }

    public function getExtensionProperty(string $property): mixed
    {
        return $this->getExtensions()[$property] ?? null;
    }

    public function setExtensionProperty(string $property, mixed $value): static
    {
        if (null === $this->extensions && null === $value) {
            return $this;
        }

        if (null === $value) {
            unset($this->extensions[$property]); // @phpstan-ignore unset.possiblyHookedProperty
        } else {
            $this->extensions[$property] = $value;
        }

        return $this;
    }
}
