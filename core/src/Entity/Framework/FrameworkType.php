<?php

namespace App\Entity\Framework;

use App\Repository\Framework\FrameworkTypeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'framework_type')]
#[ORM\Entity(repositoryClass: FrameworkTypeRepository::class)]
class FrameworkType
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $frameworkType;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFrameworkType(): ?string
    {
        return $this->frameworkType;
    }

    public function setFrameworkType(string $frameworkType): void
    {
        $this->frameworkType = $frameworkType;
    }
}
