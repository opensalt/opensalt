<?php

namespace App\Handler\Comment;

use App\Command\Comment\UpvoteCommentCommand;
use App\Entity\Comment\Comment;
use App\Event\CommandEvent;
use App\Event\NotificationEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class UpvoteCommentHandler extends BaseCommentHandler
{
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var UpvoteCommentCommand $command */
        $command = $event->getCommand();
        $this->validate($command, $command);

        $comment = $command->getComment();
        $user = $command->getUser();

        $repo = $this->em->getRepository(Comment::class);
        $repo->addUpvoteForUser($comment, $user);

        /* @todo update to fill in name and document after comments are modified */
        $notification = new NotificationEvent('C05', 'Comment up voted' /* for [Short name] */, null);
        $command->setNotificationEvent($notification);
    }
}
