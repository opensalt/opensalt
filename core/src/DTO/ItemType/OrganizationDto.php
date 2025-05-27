<?php

namespace App\DTO\ItemType;

use App\Entity\Framework\LsItem;
use App\Form\Type\ItemType\OrganizationType;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;
use Symfony\Component\Validator\Constraints as Assert;

class OrganizationDto implements ItemTypeInterface
{
    public const int ITEM_TYPE_IDENTIFIER = LsItem::TYPES['organization'];
    public const string ITEM_TYPE_FORM = OrganizationType::class;
    public const string TYPE_KEY = 'ceterms:agentType'; // value should be embedded as ceterms:targetNode in a ceterms:CredentialAlignmentObject
    public const string WEBPAGE_KEY = 'ceterms:subjectWebpage';
    public const string JURISDICTION_KEY = 'ceterms:jurisdiction'; // ceterms:jurisdiction is a ceterms:JurisdictionProfile
    public const string LOGO_KEY = 'ceterms:image';
    public const string LEGAL_NAME_KEY = 'sdo:legalName';
    public const string CTID_KEY = 'ceterms:ctid';
    public const string RORID_KEY = 'salt:rorId';

    public function __construct(
        #[Assert\NotBlank()]
        #[Assert\Length(max: 255)]
        public ?string $name = null,
        #[Assert\NotBlank()]
        public ?string $description = null,
        public ?string $type = null,
        #[Assert\Url(requireTld: true)]
        public ?string $webpage = null,
        public ?string $logo = null,
        public ?string $jurisdiction = null,
        public ?string $legalName = null,
        public ?string $ctid = null,
        public ?string $rorId = null,
    ) {
    }

    #[\Override]
    public static function fromItem(LsItem $item): self
    {
        return new self(
            $item->getAbbreviatedStatement(),
            $item->getFullStatement(),
            $item->getExtensionProperty(self::TYPE_KEY),
            $item->getExtensionProperty(self::WEBPAGE_KEY),
            $item->getExtensionProperty(self::LOGO_KEY),
            $item->getExtensionProperty(self::JURISDICTION_KEY),
            $item->getExtensionProperty(self::LEGAL_NAME_KEY),
            $item->getExtensionProperty(self::CTID_KEY),
            $item->getExtensionProperty(self::RORID_KEY)
        );
    }

    #[\Override]
    public function applyToItem(LsItem $item, HtmlSanitizerInterface $htmlSanitizer): void
    {
        $item->setAbbreviatedStatement($this->name);
        $item->setFullStatement($this->description);
        $item->setExtensionProperty(LsItem::TYPE_KEY, 'organization');
        $item->setExtensionProperty(self::TYPE_KEY, $this->type);
        $item->setExtensionProperty(self::WEBPAGE_KEY, $this->webpage);
        $item->setExtensionProperty(self::LOGO_KEY, $this->logo);
        $item->setExtensionProperty(self::JURISDICTION_KEY, $this->jurisdiction);
        $item->setExtensionProperty(self::LEGAL_NAME_KEY, $this->legalName);
        $item->setExtensionProperty(self::CTID_KEY, $this->ctid);
        $item->setExtensionProperty(self::RORID_KEY, $this->rorId);
    }
}
