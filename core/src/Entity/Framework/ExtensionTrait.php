<?php

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
        $this->extensions = $extensions;

        return $this;
    }

    public function getExtensionProperty(string $property): mixed
    {
        return $this->extensions[$property] ?? null;
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
