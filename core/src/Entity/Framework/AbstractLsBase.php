<?php

namespace App\Entity\Framework;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\MappedSuperclass()
 */
class AbstractLsBase implements IdentifiableInterface
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected ?int $id = null;

    /**
     * @var string
     *
     * @ORM\Column(name="identifier", type="string", length=300, nullable=false, unique=true)
     *
     * @Assert\NotBlank()
     * @Assert\Uuid(strict=false)
     * @Assert\Length(max=300)
     */
    protected ?string $identifier = null;

    /**
     * @var string
     *
     * @ORM\Column(name="uri", type="string", length=300, nullable=true, unique=true)
     *
     * @Assert\NotBlank()
     * @Assert\Length(max=300)
     */
    protected ?string $uri = null;

    /**
     * @var array
     *
     * @ORM\Column(name="extra", type="json", nullable=true)
     */
    protected ?array $extra = null;

    /**
     * @ORM\Column(name="changed_at", type="datetime", precision=6)
     * @Gedmo\Timestampable(on="update")
     */
    private \DateTimeInterface $changedAt;

    /**
     * @ORM\Column(name="updated_at", type="datetime", precision=6)
     * @Gedmo\Timestampable(on="update")
     */
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

    /**
     * Clone the object.
     */
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
     * Set identifier.
     *
     * @return static
     *
     * @throws \InvalidArgumentException
     */
    public function setIdentifier(UuidInterface|string $identifier)
    {
        // If the identifier is in the form of a UUID then lower case it
        if ($identifier instanceof UuidInterface) {
            $identifier = strtolower($identifier->toString());
        } elseif (is_string($identifier) && Uuid::isValid($identifier)) {
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

    /**
     * @return static
     */
    public function setUri(string $uri)
    {
        $this->uri = $uri;

        return $this;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * @return static
     */
    public function setChangedAt(\DateTimeInterface $changedAt)
    {
        $this->changedAt = $changedAt;

        return $this;
    }

    public function getChangedAt(): \DateTimeInterface
    {
        return $this->changedAt;
    }

    /**
     * @return static
     */
    public function setUpdatedAt(\DateTimeInterface $updatedAt)
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
        if (null === $this->extra) {
            return [];
        }

        return $this->extra;
    }

    /**
     * @return static
     */
    public function setExtra(?array $extra)
    {
        $this->extra = $extra;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getExtraProperty(string $property)
    {
        return $this->extra[$property] ?? null;
    }

    /**
     * @return static
     */
    public function setExtraProperty(string $property, $value)
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
