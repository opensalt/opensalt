<?php

declare(strict_types=1);

namespace App\Service\Markdown;

use App\Entity\Framework\LsItem;

interface ItemSectionRenderer
{
    public function supports(LsItem $item): bool;

    /**
     * @param array{markdown-package?: bool} $context
     */
    public function render(LsItem $item, array $context = []): string;
}
