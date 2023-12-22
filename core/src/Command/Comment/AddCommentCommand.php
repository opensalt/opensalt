<?php

namespace App\Command\Comment;

use App\Command\BaseCommand;
use App\Entity\Comment\Comment;
use App\Entity\Framework\LsDoc;
use App\Entity\Framework\LsItem;
use App\Entity\User\User;
use Symfony\Component\Validator\Constraints as Assert;

class AddCommentCommand extends BaseCommand
{
    #[Assert\Type(LsItem::class)]
    private ?LsItem $item = null;

    #[Assert\Type(LsDoc::class)]
    private ?LsDoc $document = null;

    #[Assert\Type(Comment::class)]
    private ?Comment $comment = null;

    public function __construct(
        #[Assert\Type('string')]
        #[Assert\NotNull]
        private readonly string $itemType,
        LsItem|LsDoc $itemId,
        #[Assert\Type(User::class)]
        #[Assert\NotNull]
        private readonly User $user,
        #[Assert\Type('string')]
        private readonly ?string $content = null,
        #[Assert\Type('string')]
        private readonly ?string $fileUrl = null,
        #[Assert\Type('string')]
        private readonly ?string $mimeType = null,
        #[Assert\Type('int')]
        private readonly ?int $parentId = null,
    ) {
        if ('item' === $this->itemType) {
            $this->item = $itemId;
        } else {
            $this->document = $itemId;
        }
    }

    public function getItemType(): string
    {
        return $this->itemType;
    }

    public function getItem(): LsItem
    {
        return $this->item;
    }

    public function getDocument(): LsDoc
    {
        return $this->document;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function getParentId(): int
    {
        return $this->parentId;
    }

    public function getComment(): ?Comment
    {
        return $this->comment;
    }

    public function getFileUrl(): ?string
    {
        return $this->fileUrl;
    }

    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    public function setComment(Comment $comment): void
    {
        $this->comment = $comment;
    }
}
