<?php

namespace App\Entity\Framework;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

/**
 * @ORM\MappedSuperclass()
 *
 * @Serializer\ExclusionPolicy("all")
 */
class AbstractLsDefinition extends AbstractLsBase
{
    /**
     * @ORM\Column(name="title", type="string", length=1024, nullable=true)
     *
     * @Serializer\Expose()
     */
    protected ?string $title = null;

    /**
     * @ORM\Column(name="description", type="text", nullable=true)
     *
     * @Serializer\Expose()
     */
    protected ?string $description = null;

    /**
     * @return static
     */
    public function setTitle(?string $title)
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * @return static
     */
    public function setDescription(?string $description)
    {
        $this->description = $description;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }
}
