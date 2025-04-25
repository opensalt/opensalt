<?php

namespace App\DTO\ItemType;

use App\Entity\Framework\LsItem;
use App\Form\Type\LsItemCourseType;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;
use Symfony\Component\Validator\Constraints as Assert;

class CourseDto implements ItemTypeInterface
{
    public const int ITEM_TYPE_IDENTIFIER = LsItem::TYPES['course'];
    public const string ITEM_TYPE_FORM = LsItemCourseType::class;
    public const string WEBPAGE_KEY = 'ceterms:subjectWebpage';
    public const string DELIVERY_TYPE_KEY = 'ceterms:deliveryType';

    public function __construct(
        #[Assert\NotBlank()]
        #[Assert\Length(max: 255)]
        public ?string $name = null,
        #[Assert\NotBlank()]
        public ?string $description = null,
        #[Assert\Url(requireTld: true)]
        public ?string $webpage = null,
        public ?string $codedNotation = null,
        public ?string $inLanguage = null,
        public ?string $deliveryType = null,
    ) {
    }

    #[\Override]
    public static function fromItem(LsItem $item): self
    {
        return new self(
            $item->getAbbreviatedStatement(),
            $item->getFullStatement(),
            $item->getExtensionProperty(self::WEBPAGE_KEY),
            $item->getHumanCodingScheme(),
            $item->getLanguage(),
            $item->getExtensionProperty(self::DELIVERY_TYPE_KEY)
        );
    }

    #[\Override]
    public function applyToItem(LsItem $item, HtmlSanitizerInterface $htmlSanitizer): void
    {
        $item->setAbbreviatedStatement($this->name);
        $item->setFullStatement($this->description);
        $item->setHumanCodingScheme($this->codedNotation);
        $item->setLanguage($this->inLanguage);
        $item->setExtensionProperty(LsItem::TYPE_KEY, 'course');
        $item->setExtensionProperty(self::WEBPAGE_KEY, $this->webpage);
        $item->setExtensionProperty(self::DELIVERY_TYPE_KEY, $this->deliveryType);
    }
}
