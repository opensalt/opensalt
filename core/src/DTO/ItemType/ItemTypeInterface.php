<?php

declare(strict_types=1);

namespace App\DTO\ItemType;

use App\Entity\Framework\LsItem;
use App\Form\Type\LsItemType;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;

interface ItemTypeInterface
{
    public const int ITEM_TYPE_IDENTIFIER = 0;
    public const string ITEM_TYPE_FORM = LsItemType::class;

    public static function fromItem(LsItem $item): self;

    public function applyToItem(LsItem $item, HtmlSanitizerInterface $htmlSanitizer): void;
}
