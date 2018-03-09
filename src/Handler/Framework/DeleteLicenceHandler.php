<?php

namespace App\Handler\Framework;

use App\Command\Framework\DeleteLicenceCommand;
use App\Event\CommandEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class DeleteLicenceHandler extends BaseFrameworkHandler
{
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var DeleteLicenceCommand $command */
        $command = $event->getCommand();

        $licence = $command->getLicence();
        $this->validate($command, $licence);

        $this->framework->deleteLicence($licence);
    }
}
