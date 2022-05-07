<?php

namespace App\Entity\Framework;

use App\Repository\Framework\LsDefSubjectRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'ls_def_subject')]
#[ORM\Entity(repositoryClass: LsDefSubjectRepository::class)]
class LsDefSubject extends AbstractLsDefinition implements CaseApiInterface
{
    #[ORM\Column(name: 'hierarchy_code', type: 'string', length: 255)]
    private string $hierarchyCode;

    public function getHierarchyCode(): string
    {
        return $this->hierarchyCode;
    }

    public function setHierarchyCode(string $hierarchyCode): void
    {
        $this->hierarchyCode = $hierarchyCode;
    }

    public function __toString(): string
    {
        return $this->getTitle() ?? 'Subject: '.$this->identifier;
    }
}
