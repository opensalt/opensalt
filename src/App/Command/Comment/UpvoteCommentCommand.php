<?php

namespace App\Command\Comment;

use App\Command\BaseCommand;
use Salt\SiteBundle\Entity\Comment;
use Salt\UserBundle\Entity\User;
use Symfony\Component\Validator\Constraints as Assert;

class UpvoteCommentCommand extends BaseCommand
{
    /**
     * @var Comment
     *
     * @Assert\Type(Comment::class)
     * @Assert\NotNull()
     */
    private $comment;

    /**
     * @var User
     *
     * @Assert\Type(User::class)
     * @Assert\NotNull()
     */
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
