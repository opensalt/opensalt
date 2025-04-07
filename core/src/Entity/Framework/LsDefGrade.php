<?php

namespace App\Entity\Framework;

use App\Repository\Framework\LsDefGradeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'ls_def_grade')]
#[ORM\Entity(repositoryClass: LsDefGradeRepository::class)]
class LsDefGrade extends AbstractLsBase
{
    #[ORM\Column(name: 'title', type: 'string', length: 1024, nullable: true)]
    protected ?string $title = null;

    #[ORM\Column(name: 'description', type: 'text', nullable: true)]
    protected ?string $description = null;

    #[ORM\Column(name: 'code', type: 'string', length: 255)]
    private string $code;

    #[ORM\Column(name: 'rank', type: 'integer', nullable: true)]
    private int $rank;

    public function setTitle(?string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description ?? $this->title;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): void
    {
        $this->code = $code;
    }

    public function getRank(): int
    {
        return $this->rank;
    }

    public function setRank(int $rank): void
    {
        $this->rank = $rank;
    }

    public function getLabel(): string
    {
        return "{$this->getCode()} - {$this->getTitle()}";
    }
}
