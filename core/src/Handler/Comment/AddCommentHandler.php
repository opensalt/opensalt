<?php

declare(strict_types=1);

namespace App\Handler\Comment;

use App\Command\Comment\AddCommentCommand;
use App\Entity\Comment\Comment;
use App\Event\CommandEvent;
use App\Event\NotificationEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class AddCommentHandler extends BaseCommentHandler
{
    #[\Override]
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var AddCommentCommand $command */
        $command = $event->getCommand();
        $this->validate($command, $command);

        $itemType = $command->getItemType();
        $itemId = $command->getItem();
        $user = $command->getUser();
        $content = $command->getContent();
        $parentId = $command->getParentId();
        if (0 === $parentId) {
            $parentId = null;
        }
        $fileUrl = $command->getFileUrl();
        $mimeType = $command->getMimeType();

        $repo = $this->em->getRepository(Comment::class);

        $comment = $repo->addComment($itemType, $itemId, $user, $content, $fileUrl, $mimeType, $parentId);

        $command->setComment($comment);

        /* @todo update to fill in name and document after comments are modified */
        if ($comment->getParent()) {
            $notification = new NotificationEvent('C02', 'Comment reply made' /* on [Short name] */, $comment->getDocument() ?? $comment->getItem()->getLsDoc());
        } else {
            $notification = new NotificationEvent('C01', 'Comment added' /* to [Short name] */, $comment->getDocument() ?? $comment->getItem()->getLsDoc());
        }
        $command->setNotificationEvent($notification);
    }
}
