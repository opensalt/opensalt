<?php

namespace App\DTO\ItemType;

use App\Entity\Framework\LsItem;
use App\Form\Type\LsItemJobType;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;
use Symfony\Component\Validator\Constraints as Assert;

class JobDto implements ItemTypeInterface
{
    public const int ITEM_TYPE_IDENTIFIER = LsItem::TYPES['job'];
    public const string ITEM_TYPE_FORM = LsItemJobType::class;
    public const string WEBPAGE_KEY = 'ceterms:subjectWebpage';

    public function __construct(
        #[Assert\NotBlank()]
        #[Assert\Length(max: 255)]
        public ?string $title = null,
        #[Assert\NotBlank()]
        public ?string $description = null,
        public ?string $codedNotation = null,
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
            $item->getHumanCodingScheme(),
            $item->getConceptKeywordsString(),
            $item->getExtensionProperty(self::WEBPAGE_KEY)
        );
    }

    #[\Override]
    public function applyToItem(LsItem $item, HtmlSanitizerInterface $htmlSanitizer): void
    {
        $item->setAbbreviatedStatement($this->title);
        $item->setFullStatement($this->description);
        $item->setHumanCodingScheme($this->codedNotation);
        $item->setConceptKeywordsString($this->keywords);
        $item->setExtensionProperty(LsItem::TYPE_KEY, 'job');
        $item->setExtensionProperty(self::WEBPAGE_KEY, $this->webpage);
    }
}
