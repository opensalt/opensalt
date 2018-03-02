<?php

namespace App\Handler\Framework;

use App\Command\Framework\AddLicenceCommand;
use App\Event\CommandEvent;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class AddLicenceHandler
 *
 * @DI\Service()
 */
class AddLicenceHandler extends BaseFrameworkHandler
{
    /**
     * @DI\Observe(App\Command\Framework\AddLicenceCommand::class)
     *
     * @param CommandEvent $event
     * @param string $eventName
     * @param EventDispatcherInterface $dispatcher
     *
     * @throws \Exception
     */
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var AddLicenceCommand $command */
        $command = $event->getCommand();

        $licence = $command->getLicence();
        $this->validate($command, $licence);

        $this->framework->persistLicence($licence);
    }
}
