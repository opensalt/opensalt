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
class LogCommandSuccessHandler
{
    /**
     * @DI\Observe(LogCommandSuccessCommand::class)
     */
    public function handle(CommandEvent $event, $eventName, EventDispatcherInterface $dispatcher)
    {
        $command = $event->getCommand();
    }
}
