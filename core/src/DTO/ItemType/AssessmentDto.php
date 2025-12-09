<?php

declare(strict_types=1);

namespace App\DTO\ItemType;

use App\Entity\Framework\LsItem;
use App\Entity\Framework\LsItemKind;
use App\Form\Type\ItemType\AssessmentType;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;
use Symfony\Component\Validator\Constraints as Assert;

class AssessmentDto implements ItemTypeInterface
{
    public const int ITEM_TYPE_IDENTIFIER = LsItemKind::Assessment->value;
    public const string ITEM_TYPE_FORM = AssessmentType::class;
    public const string WEBPAGE_KEY = 'ceterms:subjectWebpage';
    public const string DELIVERY_TYPE_KEY = 'ceterms:deliveryType';

    public function __construct(
        #[Assert\NotBlank()]
        #[Assert\Length(max: 255)]
        public ?string $name = null,
        #[Assert\NotBlank()]
        public ?string $description = null,
        public ?string $deliveryType = null,
        public ?string $inLanguage = null,
        public ?string $keywords = null,
        #[Assert\Url(message: 'The webpage must be a valid URL.', requireTld: true)]
        public ?string $webpage = null,
    ) {
    }

    #[\Override]
    public static function fromItem(LsItem $item): self
    {
        return new self(
            $item->getAbbreviatedStatement(),
            $item->getFullStatement(),
            $item->getExtensionProperty(self::DELIVERY_TYPE_KEY),
            $item->getLanguage(),
            $item->getConceptKeywordsString(),
            $item->getExtensionProperty(self::WEBPAGE_KEY)
        );
    }

    #[\Override]
    public function applyToItem(LsItem $item, HtmlSanitizerInterface $htmlSanitizer): void
    {
        $item->setAbbreviatedStatement($this->name);
        $item->setFullStatement($this->description);
        $item->setConceptKeywordsString($this->keywords);
        $item->setLanguage($this->inLanguage);
        $item->setExtensionProperty(LsItem::TYPE_KEY, 'assessment');
        $item->setExtensionProperty(self::WEBPAGE_KEY, $this->webpage);
        $item->setExtensionProperty(self::DELIVERY_TYPE_KEY, $this->deliveryType);
    }
}
