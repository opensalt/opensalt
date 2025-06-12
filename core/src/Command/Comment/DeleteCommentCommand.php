<?php

declare(strict_types=1);

namespace App\Command\Comment;

use App\Command\BaseCommand;
use App\Entity\Comment\Comment;
use Symfony\Component\Validator\Constraints as Assert;

class DeleteCommentCommand extends BaseCommand
{
    public function __construct(
        #[Assert\Type(Comment::class)]
        #[Assert\NotNull]
        private readonly Comment $comment,
    ) {
    }

    public function getComment(): Comment
    {
        return $this->comment;
    }
}
