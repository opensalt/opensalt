<?php

namespace App\Entity\Framework;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="ls_def_framework_type")
 * @ORM\Entity(repositoryClass="App\Repository\Framework\LsDefFrameworkTypeRepository")
 */
class LsDefFrameworkType
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
    private $value;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(string $value): self
    {
        $this->value = $value;

        return $this;
    }
}
