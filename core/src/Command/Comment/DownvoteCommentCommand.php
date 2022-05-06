<?php

namespace App\Command\Comment;

use App\Command\BaseCommand;
use App\Entity\Comment\Comment;
use App\Entity\User\User;
use Symfony\Component\Validator\Constraints as Assert;

class DownvoteCommentCommand extends BaseCommand
{
    /**
     * @var Comment
     */
    #[Assert\Type(Comment::class)]
    #[Assert\NotNull]
    private $comment;

    /**
     * @var User
     */
    #[Assert\Type(User::class)]
    #[Assert\NotNull]
    private $user;

    public function __construct(Comment $comment, User $user)
    {
        $this->comment = $comment;
        $this->user = $user;
    }

    public function getComment(): Comment
    {
        return $this->comment;
    }

    public function getUser(): User
    {
        return $this->user;
    }
}
