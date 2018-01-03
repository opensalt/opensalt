<?php

namespace App\Command\Comment;

use App\Command\BaseCommand;
use Salt\SiteBundle\Entity\Comment;
use Salt\UserBundle\Entity\User;
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
     * @var int
     *
     * @Assert\Type("int")
     * @Assert\NotNull()
     */
    private $itemId;

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
     * @Assert\NotNull()
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

    public function __construct(string $itemType, $itemId, User $user, string $content, $parentId = null)
    {
        $this->itemType = $itemType;
        $this->itemId = (int) $itemId;
        $this->user = $user;
        $this->content = $content;
        $this->parentId = (int) $parentId;
    }

    public function getItemType(): string
    {
        return $this->itemType;
    }

    public function getItemId(): int
    {
        return $this->itemId;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getContent(): string
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

    public function setComment(Comment $comment): void
    {
        $this->comment = $comment;
    }
}
