<?php

declare(strict_types=1);

namespace App\Command\Comment;

use App\Command\BaseCommand;
use App\Entity\Comment\Comment;
use App\Entity\User\User;
use Symfony\Component\Validator\Constraints as Assert;

class UpvoteCommentCommand extends BaseCommand
{
    public function __construct(
        #[Assert\Type(Comment::class)]
        #[Assert\NotNull]
        private readonly Comment $comment,
        #[Assert\Type(User::class)]
        #[Assert\NotNull]
        private readonly User $user,
    ) {
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
