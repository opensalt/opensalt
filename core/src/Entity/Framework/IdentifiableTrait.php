<?php

declare(strict_types=1);

namespace App\Entity\Framework;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Validator\Constraints as Assert;

trait IdentifiableTrait
{
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    #[ORM\Column(name: 'identifier', type: 'string', length: 300, unique: true, nullable: false)]
    #[Assert\NotBlank()]
    #[Assert\Uuid(strict: false)]
    #[Assert\Length(max: 300)]
    private ?string $identifier = null;

    #[ORM\Column(name: 'uri', type: 'string', length: 300, unique: true, nullable: true)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 300)]
    private ?string $uri = null;

    #[ORM\Column(name: 'changed_at', type: 'datetime', precision: 6)]
    #[Gedmo\Timestampable(on: 'update')]
    private \DateTimeInterface $changedAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime', precision: 6)]
    #[Gedmo\Timestampable(on: 'update')]
    private \DateTimeInterface $updatedAt;

    /**
     * Get the internal id of the object (or null if not persisted).
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    public function setIdentifierOrNew(UuidInterface|string|null $identifier = null): void
    {
        if (null === $identifier) {
            $identifier = Uuid::uuid1()->toString();
        }

        $this->setIdentifier($identifier);
        $this->uri = 'local:'.$this->identifier;
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function setIdentifier(UuidInterface|string $identifier): static
    {
        // If the identifier is in the form of a UUID then lower case it
        if ($identifier instanceof UuidInterface) {
            $identifier = strtolower($identifier->toString());
        } elseif (Uuid::isValid($identifier)) {
            $identifier = strtolower(Uuid::fromString($identifier)->toString());
        } else {
            throw new \InvalidArgumentException('The identifier must be a UUID.');
        }

        $this->identifier = $identifier;

        return $this;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function setUri(string $uri): static
    {
        $this->uri = $uri;

        return $this;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

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
