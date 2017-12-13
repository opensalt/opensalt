<?php

namespace App\Command\Logger;

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
    }
}
