<?php

declare(strict_types=1);

namespace App\DTO\ItemType;

use App\Entity\Framework\LsItem;
use App\Form\Type\ItemType\IdentifierType;
use App\Form\Validator\ValidUri;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;
use Symfony\Component\Validator\Constraints as Assert;

class IdentifierDto implements ItemTypeInterface
{
    public const int ITEM_TYPE_IDENTIFIER = LsItem::TYPES['identifier'];
    public const string ITEM_TYPE_FORM = IdentifierType::class;
    public const string TYPE_KEY = 'salt:idType';

    public function __construct(
        #[Assert\NotNull()]
        #[Assert\NotBlank()]
        #[Assert\Length(max: 255)]
        #[ValidUri()]
        public ?string $identifier = null,
        #[Assert\NotNull()]
        #[Assert\NotBlank()]
        public ?string $description = null,
        public ?string $type = null,
    ) {
    }

    #[\Override]
    public static function fromItem(LsItem $item): self
    {
        return new self(
            $item->getAbbreviatedStatement(),
            $item->getFullStatement(),
            $item->getExtensionProperty(self::TYPE_KEY)
        );
    }

    #[\Override]
    public function applyToItem(LsItem $item, HtmlSanitizerInterface $htmlSanitizer): void
    {
        $item->setAbbreviatedStatement($this->identifier);
        $item->setUri($this->identifier);
        $item->setFullStatement($this->description);
        $item->setExtensionProperty(LsItem::TYPE_KEY, 'identifier');
        $item->setExtensionProperty(self::TYPE_KEY, $this->type);
    }
}
