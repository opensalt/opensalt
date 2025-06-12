<?php

declare(strict_types=1);

namespace App\Command\Comment;

use App\Command\BaseCommand;
use App\Entity\Comment\Comment;
use Symfony\Component\Validator\Constraints as Assert;

class UpdateCommentCommand extends BaseCommand
{
    public function __construct(
        #[Assert\Type(Comment::class)]
        #[Assert\NotNull]
        private readonly Comment $comment,
        #[Assert\Type('string')]
        #[Assert\NotNull]
        #[Assert\NotBlank]
        private readonly string $newContent,
    ) {
    }

    public function getComment(): Comment
    {
        return $this->comment;
    }

    public function getNewContent(): string
    {
        return $this->newContent;
    }
}
