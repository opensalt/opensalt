<?php

namespace App\Command\Framework;

use App\Command\BaseCommand;
use CftfBundle\Entity\LsDoc;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class UpdateTreeItemsCommand extends BaseCommand
{
    /**
     * @var LsDoc
     *
     * @Assert\Type(LsDoc::class)
     * @Assert\NotNull()
     */
    private $doc;

    /**
     * @var array
     *
     * @Assert\Type("array")
     * @Assert\NotNull()
     */
    private $items;

    /**
     * @var array
     */
    private $rv;

    /**
     * Constructor.
     */
    public function __construct(LsDoc $doc, array $items)
    {
        $this->doc = $doc;
        $this->items = $items;
        $this->rv = [];
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

    /**
     * @Assert\Callback()
     */
    public function validate(ExecutionContextInterface $context, $payload): void
    {
        foreach ($this->items as $itemId => $updates) {
            if (empty($updates['originalKey'])) {
                $context->buildViolation("originalKey must be supplied for update item {$itemId}.")
                    ->atPath('items')
                    ->addViolation();
            }
        }
    }
}
