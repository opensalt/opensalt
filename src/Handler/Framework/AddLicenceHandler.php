<?php

namespace App\Handler\Framework;

use App\Command\Framework\AddLicenceCommand;
use App\Event\CommandEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class AddLicenceHandler extends BaseFrameworkHandler
{
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var AddLicenceCommand $command */
        $command = $event->getCommand();

        $licence = $command->getLicence();
        $this->validate($command, $licence);

        $this->framework->persistLicence($licence);
    }
}
