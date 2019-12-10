<?php

namespace App\Entity\Framework;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="salt_association_subtype")
 * @ORM\Entity(repositoryClass="App\Repository\Framework\AssociationSubtypeRepository")
 */
class AssociationSubtype
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private int $id;

    /**
     * @ORM\Column(type="string", length=255)
     *
     * @Assert\NotBlank()
     * @Assert\NotNull()
     */
    private string $name;

    /**
     * @ORM\Column(type="string", length=255)
     *
     * @Assert\NotBlank()
     * @Assert\NotNull()
     * @Assert\Choice({
     *     "Is Child Of",
     *     "Exact Match Of",
     *     "Is Related To",
     *     "Is Part Of",
     *     "Replaced By",
     *     "Precedes",
     *     "Has Skill Level",
     *     "Is Peer Of",
     *     "Exemplar",
     * })
     */
    private string $parentType;

    /**
     * @ORM\Column(type="boolean")
     *
     * @Assert\NotNull()
     */
    private bool $inverseParent;

    /**
     * @ORM\Column(type="string", length=512)
     */
    private string $description;

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getParentType(): string
    {
        return $this->parentType;
    }

    public function isInverseParent(): bool
    {
        return $this->inverseParent;
    }

    public function getDescription(): string
    {
        return $this->description;
    }
}
