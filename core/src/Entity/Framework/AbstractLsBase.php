<?php

namespace App\Entity\Framework;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\MappedSuperclass]
class AbstractLsBase implements IdentifiableInterface
{
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(name: 'identifier', type: 'string', length: 300, unique: true, nullable: false)]
    #[Assert\NotBlank]
    #[Assert\Uuid(strict: false)]
    #[Assert\Length(max: 300)]
    protected ?string $identifier = null;

    #[ORM\Column(name: 'uri', type: 'string', length: 300, unique: true, nullable: true)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 300)]
    protected ?string $uri = null;

    #[ORM\Column(name: 'extra', type: 'json', nullable: true)]
    protected ?array $extra = null;

    /**
     * @Gedmo\Timestampable(on="update")
     */
    #[ORM\Column(name: 'changed_at', type: 'datetime', precision: 6)]
    private \DateTimeInterface $changedAt;

    /**
     * @Gedmo\Timestampable(on="update")
     */
    #[ORM\Column(name: 'updated_at', type: 'datetime', precision: 6)]
    protected \DateTimeInterface $updatedAt;

    public function __construct(UuidInterface|string|null $identifier = null)
    {
        if ($identifier instanceof UuidInterface) {
            $identifier = strtolower($identifier->toString());
        } elseif (is_string($identifier) && Uuid::isValid($identifier)) {
            $identifier = strtolower(Uuid::fromString($identifier)->toString());
        } else {
            $identifier = Uuid::uuid1()->toString();
        }

        $this->identifier = $identifier;
        $this->uri = 'local:'.$this->identifier;

        $this->updatedAt = new \DateTimeImmutable();
        $this->changedAt = $this->updatedAt;
    }

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

    /**
     * Get the internal id of the object (or null if not persisted).
     */
    public function getId(): ?int
    {
        return $this->id;
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
            unset($this->extra[$property]);
        } else {
            $this->extra[$property] = $value;
        }

        return $this;
    }
}
