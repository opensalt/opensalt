<?php

declare(strict_types=1);

namespace App\Command\Framework;

use App\Command\BaseCommand;
use App\Entity\Framework\LsDoc;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class UpdateTreeItemsCommand extends BaseCommand
{
    private array $rv = [];

    /**
     * Constructor.
     */
    public function __construct(
        #[Assert\Type(LsDoc::class)]
        #[Assert\NotNull]
        private readonly LsDoc $doc,
        #[Assert\Type('array')]
        #[Assert\NotNull]
        private readonly array $items,
    ) {
    }

    public function getDoc(): LsDoc
    {
        return $this->doc;
    }

    public function getItems(): array
    {
        return $this->items;
    }

    public function setReturnValues(array $rv): void
    {
        $this->rv = $rv;
    }

    public function getReturnValues(): array
    {
        return $this->rv;
    }

    #[Assert\Callback]
    public function validate(ExecutionContextInterface $context, $payload): void
    {
        foreach ($this->items as $itemId => $updates) {
            if (empty($updates['originalKey'])) {
                $context->buildViolation(sprintf('originalKey must be supplied for update item %s.', $itemId))
                    ->atPath('items')
                    ->addViolation();
            }
        }
    }
}
