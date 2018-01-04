<?php

namespace App\Handler\Comment;

use App\Command\Comment\DeleteCommentCommand;
use App\Event\CommandEvent;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @DI\Service()
 */
class DeleteCommentHandler extends BaseCommentHandler
{
    /**
     * @DI\Observe(App\Command\Comment\DeleteCommentCommand::class)
     */
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var DeleteCommentCommand $command */
        $command = $event->getCommand();

        $comment = $command->getComment();
        $this->em->remove($comment);
    }
}
