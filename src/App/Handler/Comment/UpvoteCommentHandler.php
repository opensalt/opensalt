<?php

namespace App\Handler\Comment;

use App\Command\Comment\UpvoteCommentCommand;
use App\Event\CommandEvent;
use JMS\DiExtraBundle\Annotation as DI;
use Salt\SiteBundle\Entity\Comment;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @DI\Service()
 */
class UpvoteCommentHandler extends BaseCommentHandler
{
    /**
     * @DI\Observe(App\Command\Comment\UpvoteCommentCommand::class)
     */
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var UpvoteCommentCommand $command */
        $command = $event->getCommand();
        $this->validate($command, $command);

        $comment = $command->getComment();
        $user = $command->getUser();

        $repo = $this->em->getRepository(Comment::class);
        $repo->addUpvoteForUser($comment, $user);
    }
}
