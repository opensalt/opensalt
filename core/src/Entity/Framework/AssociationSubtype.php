<?php

namespace App\Entity\Framework;

use App\Repository\Framework\AssociationSubtypeRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: 'salt_association_subtype')]
#[ORM\Entity(repositoryClass: AssociationSubtypeRepository::class)]
class AssociationSubtype
{
    final public const DIR_BOTH = 0;
    final public const DIR_FORWARD = 1;
    final public const DIR_INVERSE = -1;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    #[Assert\NotBlank]
    #[Assert\NotNull]
    private string $name;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank]
    #[Assert\NotNull]
    #[Assert\Choice(['Is Child Of', 'Exact Match Of', 'Is Related To', 'Is Part Of', 'Replaced By', 'Precedes', 'Has Skill Level', 'Is Peer Of', 'Exemplar'])]
    private string $parentType;

    #[ORM\Column(type: 'integer')]
    #[Assert\NotNull]
    private int $direction;

    #[ORM\Column(type: 'string', length: 512)]
    #[Assert\NotNull]
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

    public function getDirection(): int
    {
        return $this->direction;
    }

    public function getDescription(): string
    {
        return $this->description;
    }
}
