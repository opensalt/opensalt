<?php

namespace App\Handler\Framework;

use App\Command\Framework\UpdateLicenceCommand;
use App\Event\CommandEvent;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class UpdateLicenceHandler
 *
 * @DI\Service()
 */
class UpdateLicenceHandler extends BaseFrameworkHandler
{
    /**
     * @DI\Observe(App\Command\Framework\UpdateLicenceCommand::class)
     *
     * @param CommandEvent $event
     * @param string $eventName
     * @param EventDispatcherInterface $dispatcher
     *
     * @throws \Exception
     */
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var UpdateLicenceCommand $command */
        $command = $event->getCommand();

        $licence = $command->getLicence();
        $this->validate($command, $licence);
    }
}
