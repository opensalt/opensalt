<?php

namespace App\Handler\Comment;

use App\Command\Comment\UpdateCommentCommand;
use App\Event\CommandEvent;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @DI\Service()
 */
class UpdateCommentHandler extends BaseCommentHandler
{
    /**
     * @DI\Observe(App\Command\Comment\UpdateCommentCommand::class)
     */
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var UpdateCommentCommand $command */
        $command = $event->getCommand();
        $this->validate($command, $command);

        $comment = $command->getComment();
        $this->validate($command, $comment);

        $content = $command->getNewContent();

        $comment->setContent($content);
    }
}
