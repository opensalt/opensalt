<?php

namespace App\Entity\Framework;

use App\Repository\Framework\LsDefGradeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'ls_def_grade')]
#[ORM\Entity(repositoryClass: LsDefGradeRepository::class)]
class LsDefGrade extends AbstractLsDefinition
{
    #[ORM\Column(name: 'code', type: 'string', length: 255)]
    private string $code;

    #[ORM\Column(name: 'rank', type: 'integer', nullable: true)]
    private int $rank;

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
