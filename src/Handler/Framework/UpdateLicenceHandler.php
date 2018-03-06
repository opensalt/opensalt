<?php

namespace App\Handler\Framework;

use App\Command\Framework\UpdateLicenceCommand;
use App\Event\CommandEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class UpdateLicenceHandler extends BaseFrameworkHandler
{
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var UpdateLicenceCommand $command */
        $command = $event->getCommand();

        $licence = $command->getLicence();
        $this->validate($command, $licence);
    }
}
