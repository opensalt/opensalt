<?php

namespace App\Command\Logger;

use App\Command\Logger\LogCommandStartCommand;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class LogCommandStartHandler
 *
 * @DI\Service()
 */
class LogCommandStartHandler
{
    /**
     * @DI\Observe(LogCommandStartCommand::class)
     */
    public function handle(LogCommandStartCommand $event, $eventName, EventDispatcherInterface $dispatcher)
    {
        $command = $event->getSubject();

        var_dump('starting');
        var_dump($command);
        var_dump($event->getArguments());
    }
}
