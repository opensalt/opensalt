<?php

namespace App\Command\Comment;

use App\Command\BaseCommand;
use App\Entity\Comment\Comment;
use App\Entity\User\User;
use App\Entity\Framework\LsDoc;
use App\Entity\Framework\LsItem;
use Symfony\Component\Validator\Constraints as Assert;

class AddCommentCommand extends BaseCommand
{
    /**
     * @var string
     *
     * @Assert\Type("string")
     * @Assert\NotNull()
     */
    private $itemType;

    /**
     * @var LsItem
     *
     * @assert\type(LsItem::class)
     */
    private $item;

    /**
     * @var LsDoc
     *
     * @assert\type(LsDoc::class)
     */
    private $document;

    /**
     * @var User
     *
     * @Assert\Type(User::class)
     * @Assert\NotNull()
     */
    private $user;

    /**
     * @var string
     *
     * @Assert\Type("string")
     */
    private $content;

    /**
     * @var int
     *
     * @Assert\Type("int")
     */
    private $parentId;

    /**
     * @var Comment
     *
     * @Assert\Type(Comment::class)
     */
    private $comment;

    /**
     * @var string
     *
     * @Assert\Type("string")
     */
    private $fileUrl;

    private $mimeType;

    public function __construct(string $itemType, $itemId, User $user, ?string $content = null, ?string $fileUrl = null, ?string $mimeType = null, $parentId = null)
    {
        $this->itemType = $itemType;
        if ($this->itemType === 'item') {
            $this->item = $itemId;
        } else {
            $this->document = $itemId;
        }
        $this->user = $user;
        $this->content = $content;
        $this->parentId = (int) $parentId;
        $this->fileUrl = $fileUrl;
        $this->mimeType = $mimeType;
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
