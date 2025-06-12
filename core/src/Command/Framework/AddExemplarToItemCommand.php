<?php

declare(strict_types=1);

namespace App\Command\Framework;

use App\Command\BaseCommand;
use App\Entity\Framework\LsAssociation;
use App\Entity\Framework\LsItem;
use Symfony\Component\Validator\Constraints as Assert;

class AddExemplarToItemCommand extends BaseCommand
{
    /**
     * Constructor.
     */
    public function __construct(
        #[Assert\Type(LsItem::class)]
        #[Assert\NotNull]
        private readonly LsItem $item,
        #[Assert\Type('string')]
        #[Assert\NotNull]
        #[Assert\Length(max: 300)]
        private readonly string $url,
        #[Assert\Type('string')]
        private readonly ?string $annotation = null,
        #[Assert\Type(LsAssociation::class)]
        private ?LsAssociation $association = null,
    ) {
    }

    public function getItem(): LsItem
    {
        return $this->item;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getAnnotation(): ?string
    {
        return $this->annotation;
    }

    public function getAssociation(): ?LsAssociation
    {
        return $this->association;
    }

    public function setAssociation(?LsAssociation $association): void
    {
        $this->association = $association;
    }
}
