<?php

namespace App\Command\Comment;

use App\Command\BaseCommand;
use Salt\SiteBundle\Entity\Comment;
use Symfony\Component\Validator\Constraints as Assert;

class DeleteCommentCommand extends BaseCommand
{
    /**
     * @var Comment
     *
     * @Assert\Type(Comment::class)
     * @Assert\NotNull()
     */
    private $comment;

    public function __construct(Comment $comment)
    {
        $this->comment = $comment;
    }

    public function getComment(): Comment
    {
        return $this->comment;
    }
}
