<?php

namespace App\Handler\Comment;

use App\Command\Comment\DownvoteCommentCommand;
use App\Event\CommandEvent;
use JMS\DiExtraBundle\Annotation as DI;
use Salt\SiteBundle\Entity\Comment;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @DI\Service()
 */
class DownvoteCommentHandler extends BaseCommentHandler
{
    /**
     * @DI\Observe(App\Command\Comment\DownvoteCommentCommand::class)
     */
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
