<?php

namespace App\Command\Logger;

use App\Event\CommandEvent;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class LogCommandStartHandler
 *
 * @DI\Service()
 */
class LogCommandFailedHandler
{
    /**
     * @DI\Observe(LogCommandFailedCommand::class)
     */
    public function handle(CommandEvent $event, $eventName, EventDispatcherInterface $dispatcher)
    {
        $command = $event->getCommand();
    }
}
