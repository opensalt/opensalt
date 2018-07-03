<?php

namespace App\Handler\Comment;

use App\Command\Comment\AddCommentCommand;
use App\Event\CommandEvent;
use App\Event\NotificationEvent;
use App\Entity\Comment\Comment;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class AddCommentHandler extends BaseCommentHandler
{
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var AddCommentCommand $command */
        $command = $event->getCommand();
        $this->validate($command, $command);

        $itemType = $command->getItemType();
        $itemId = ('item' === $itemType) ? $command->getItem() : $command->getDocument();
        $user = $command->getUser();
        $content = $command->getContent();
        $parentId = $command->getParentId();
        $fileUrl = $command->getFileUrl();
        $mimeType = $command->getMimeType();

        $repo = $this->em->getRepository(Comment::class);

        $comment = $repo->addComment($itemType, $itemId, $user, $content, $fileUrl, $mimeType, $parentId);

        $command->setComment($comment);

        /* @todo update to fill in name and document after comments are modified */
        if ($comment->getParent()) {
            $notification = new NotificationEvent('C02', 'Comment reply made' /* on [Short name] */, null);
        } else {
            $notification = new NotificationEvent('C01', 'Comment added' /* to [Short name] */, null);
        }
        $command->setNotificationEvent($notification);
    }
}
