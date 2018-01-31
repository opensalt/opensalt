<?php

namespace CftfBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use JMS\Serializer\Annotation as Serializer;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * AbstractLsBase
 *
 * @ORM\MappedSuperclass()
 *
 * @Serializer\ExclusionPolicy("all")
 * @Serializer\VirtualProperty(
 *     "uri",
 *     exp="service('salt.api.v1p0.utils').getApiUrl(object)",
 *     options={
 *         @Serializer\SerializedName("uri"),
 *         @Serializer\Expose()
 *     }
 * )
 */
class AbstractLsBase implements IdentifiableInterface
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @Serializer\Exclude()
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="identifier", type="string", length=300, nullable=false, unique=true)
     *
     * @Assert\NotBlank()
     * @Assert\Uuid(strict=false)
     * @Assert\Length(max=300)
     *
     * @Serializer\Expose()
     */
    private $identifier;

    /**
     * @var string
     *
     * @ORM\Column(name="uri", type="string", length=300, nullable=true, unique=true)
     *
     * @Assert\NotBlank()
     * @Assert\Length(max=300)
     *
     * @Serializer\Exclude()
     */
    private $uri;

    /**
     * @var array
     *
     * @ORM\Column(name="extra", type="json", nullable=true)
     *
     * @Serializer\Exclude()
     */
    private $extra = [];

    /**
     * @var \DateTimeInterface
     *
     * @ORM\Column(name="updated_at", type="datetime", precision=6)
     * @Gedmo\Timestampable(on="update")
     *
     * @Serializer\Expose()
     * @Serializer\SerializedName("lastChangeDateTime")
     */
    private $updatedAt;


    /**
     * Constructor
     *
     * @param string|Uuid|null $identifier
     */
    public function __construct($identifier = null)
    {
        if ($identifier instanceof Uuid) {
            $identifier = strtolower($identifier->toString());
        } elseif (is_string($identifier) && Uuid::isValid($identifier)) {
            $identifier = strtolower(Uuid::fromString($identifier)->toString());
        } else {
            $identifier = Uuid::uuid1()->toString();
        }

        $this->identifier = $identifier;
        $this->uri = 'local:'.$this->identifier;

        $this->updatedAt = new \DateTimeImmutable();
    }

    /**
     * Clone the object
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
    }

    /**
     * Get the internal id of the object (or null if not persisted)
     *
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Set identifier
     *
     * @param Uuid|string $identifier
     *
     * @return static
     *
     * @throws \InvalidArgumentException
     */
    public function setIdentifier($identifier)
    {
        // If the identifier is in the form of a UUID then lower case it
        if ($identifier instanceof Uuid) {
            $identifier = strtolower($identifier->serialize());
        } elseif (is_string($identifier) && Uuid::isValid($identifier)) {
            $identifier = strtolower(Uuid::fromString($identifier)->toString());
        } else {
            throw new \InvalidArgumentException('The identifier must be a UUID.');
        }

        $this->identifier = $identifier;

        return $this;
    }

    /**
     * Get identifier
     *
     * @return string
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * Set uri
     *
     * @param string $uri
     *
     * @return static
     */
    public function setUri(string $uri)
    {
        $this->uri = $uri;

        return $this;
    }

    /**
     * Get uri
     *
     * @return string
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * Set updatedAt
     *
     * @param \DateTimeInterface $updatedAt
     *
     * @return static
     */
    public function setUpdatedAt(\DateTimeInterface $updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Get updatedAt
     *
     * @return \DateTimeInterface
     */
    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    /**
     * @return array
     */
    public function getExtra(): array
    {
        return $this->extra;
    }

    /**
     * @param array $extra
     *
     * @return static
     */
    public function setExtra(array $extra)
    {
        $this->extra = $extra;

        return $this;
    }

    /**
     * @param string $property
     *
     * @return mixed
     */
    public function getExtraProperty(string $property)
    {
        return $this->extra[$property] ?? null;
    }

    /**
     * @param string $property
     * @param mixed $value
     *
     * @return static
     */
    public function setExtraProperty(string $property, $value)
    {
        $this->extra[$property] = $value;

        return $this;
    }
}
