<?php

declare(strict_types=1);

namespace App\Entity\Framework;

use App\Repository\Framework\LsDocAttributeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'ls_doc_attribute')]
#[ORM\Entity(repositoryClass: LsDocAttributeRepository::class)]
class LsDocAttribute
{
    final public const string IS_GRADE_LEVELS = 'isGradeLevels';

    public function __construct(
        #[ORM\Id]
        #[ORM\ManyToOne(targetEntity: LsDoc::class, inversedBy: 'attributes')]
        private LsDoc $lsDoc,
        #[ORM\Id]
        #[ORM\Column(name: 'attribute', type: 'string', length: 255)]
        private string $attribute,
        #[ORM\Column(name: 'value', type: 'string', length: 255, nullable: true)]
        private ?string $value,
    ) {
    }

    public function getLsDoc(): LsDoc
    {
        return $this->lsDoc;
    }

    public function setAttribute(string $attribute): static
    {
        $this->attribute = $attribute;

        return $this;
    }

    public function getAttribute(): string
    {
        return $this->attribute;
    }

    public function setValue(?string $value): static
    {
        $this->value = $value;

        return $this;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }
}
