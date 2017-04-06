<?php

namespace CftfBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use JMS\Serializer\Annotation as Serializer;
use Ramsey\Uuid\Uuid;

/**
 * AbstractLsBase
 *
 * @ORM\MappedSuperclass()
 *
 * @Serializer\ExclusionPolicy("all")
 * @Serializer\VirtualProperty(
 *     "uri",
 *     exp="service('salt.api.v1p1.utils').getApiUrl(object)",
 *     options={
 *         @Serializer\SerializedName("uri"),
 *         @Serializer\Expose()
 *     }
 * )
 */
class AbstractLsBase
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
     * @ORM\Column(name="identifier", type="string", length=300, nullable=false)
     *
     * @Serializer\Expose()
     */
    private $identifier;

    /**
     * @var string
     *
     * @ORM\Column(name="uri", type="string", length=300, nullable=true)
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
    private $extra;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated_at", type="datetime", columnDefinition="DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL")
     * @Gedmo\Timestampable(on="update")
     *
     * @Serializer\Expose()
     * @Serializer\SerializedName("lastChangeDateTime")
     */
    private $updatedAt;


    /**
     * Constructor
     */
    public function __construct()
    {
        $this->identifier = Uuid::uuid4()->toString();
        $this->uri = 'local:'.$this->identifier;
    }

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set identifier
     *
     * @param string $identifier
     *
     * @return self
     */
    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;

        return $this;
    }

    /**
     * Get identifier
     *
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * Set uri
     *
     * @param string $uri
     *
     * @return self
     */
    public function setUri($uri)
    {
        $this->uri = $uri;

        return $this;
    }

    /**
     * Get uri
     *
     * @return string
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * Set updatedAt
     *
     * @param \DateTime $updatedAt
     *
     * @return self
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Get updatedAt
     *
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @return array
     */
    public function getExtra() {
        return $this->extra;
    }

    /**
     * @param array $extra
     *
     * @return self
     */
    public function setExtra($extra) {
        $this->extra = $extra;

        return $this;
    }

    /**
     * @param string $property
     *
     * @return mixed
     */
    public function getExtraProperty($property) {
        if (is_null($this->extra)) {
            return null;
        }

        if (!array_key_exists($property, $this->extra)) {
            return null;
        }

        return $this->extra[$property];
    }

    /**
     * @param string $property
     * @param mixed $value
     *
     * @return self
     */
    public function setExtraProperty($property, $value) {
        if (is_null($this->extra)) {
            $this->extra = [];
        }

        $this->extra[$property] = $value;
        return $this;
    }
}
