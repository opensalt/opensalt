<?php

namespace App\Command\Comment;

use App\Command\BaseCommand;
use Salt\SiteBundle\Entity\Comment;
use Symfony\Component\Validator\Constraints as Assert;

class UpdateCommentCommand extends BaseCommand
{
    /**
     * @var Comment
     *
     * @Assert\Type(Comment::class)
     * @Assert\NotNull()
     */
    private $comment;

    /**
     * @var string
     *
     * @Assert\Type("string")
     * @Assert\NotNull()
     * @Assert\NotBlank()
     */
    private $newContent;

    public function __construct(Comment $comment, string $newContent)
    {
        $this->comment = $comment;
        $this->newContent = $newContent;
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
