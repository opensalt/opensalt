<?php

namespace App\Entity\Framework;

use Doctrine\ORM\Mapping as ORM;

/**
 * LsDefItemType
 *
 * @ORM\Table(name="ls_def_item_type")
 * @ORM\Entity(repositoryClass="App\Repository\Framework\LsDefItemTypeRepository")
 */
class LsDefItemType extends AbstractLsDefinition implements CaseApiInterface
{
    /**
     * @var string|null
     *
     * @ORM\Column(name="code", type="string", length=255, nullable=true)
     */
    private $code;

    /**
     * @var string
     *
     * @ORM\Column(name="hierarchy_code", type="string", length=255)
     */
    private $hierarchyCode;

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): LsDefItemType
    {
        $this->code = $code;

        return $this;
    }

    public function getHierarchyCode(): string
    {
        return $this->hierarchyCode;
    }

    public function setHierarchyCode(string $hierarchyCode): LsDefItemType
    {
        $this->hierarchyCode = $hierarchyCode;

        return $this;
    }

    public function __toString(): string
    {
        return $this->getTitle() ?? $this->getIdentifier();
    }
}
