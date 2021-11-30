<?php

namespace App\Entity\Framework;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\MappedSuperclass()
 */
class AbstractLsDefinition extends AbstractLsBase
{
    /**
     * @ORM\Column(name="title", type="string", length=1024, nullable=true)
     */
    protected ?string $title = null;

    /**
     * @ORM\Column(name="description", type="text", nullable=true)
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

    public function getDescriptionOrNull(): ?string
    {
        return $this->description;
    }

    public function getDescription(): ?string
    {
        return $this->description ?? $this->title;
    }
}
