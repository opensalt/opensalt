<?php

namespace App\Entity\Framework;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="framework_type")
 * @ORM\Entity(repositoryClass="App\Repository\Framework\FrameworkTypeRepository")
 */
class FrameworkType
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $frameworkType;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFrameworkType(): ?string
    {
        return $this->frameworkType;
    }

    public function setFrameworkType(string $frameworkType): self
    {
        $this->frameworkType = $frameworkType;

        return $this;
    }
}
