<?php

namespace App\Handler\Comment;

use App\Command\Comment\AddCommentCommand;
use App\Event\CommandEvent;
use App\Event\NotificationEvent;
use JMS\DiExtraBundle\Annotation as DI;
use Salt\SiteBundle\Entity\Comment;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @DI\Service()
 */
class AddCommentHandler extends BaseCommentHandler
{
    /**
     * @DI\Observe(App\Command\Comment\AddCommentCommand::class)
     */
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var AddCommentCommand $command */
        $command = $event->getCommand();
        $this->validate($command, $command);

        $itemType = $command->getItemType();
        $itemId = ($itemType == 'item') ? $command->getItem() : $command->getDocument();
        $user = $command->getUser();
        $content = $command->getContent();
        $parentId = $command->getParentId();

        $repo = $this->em->getRepository(Comment::class);

        $comment = $repo->addComment($itemType, $itemId, $user, $content, $parentId);

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
