<?php

namespace App\Handler\Comment;

use App\Command\Comment\DownvoteCommentCommand;
use App\Entity\Comment\Comment;
use App\Event\CommandEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class DownvoteCommentHandler extends BaseCommentHandler
{
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var DownvoteCommentCommand $command */
        $command = $event->getCommand();
        $this->validate($command, $command);

        $comment = $command->getComment();
        $user = $command->getUser();

        $repo = $this->em->getRepository(Comment::class);
        if (!$repo->removeUpvoteForUser($comment, $user)) {
            throw new \RuntimeException('Upvote does not exist');
        }

        /* No notification for a down vote */
    }
}
