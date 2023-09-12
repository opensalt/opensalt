<?php

namespace App\Handler;

use App\Command\CommandInterface;
use App\Event\CommandEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

abstract class BaseValidatedHandler implements EventSubscriberInterface
{
    public function __construct(protected ValidatorInterface $validator)
    {
    }

    abstract public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void;

    public function validate(CommandInterface $command, object $toValidate): void
    {
        $errors = $this->validator->validate($toValidate);
        if (\count($errors)) {
            $command->setValidationErrors($errors);

            $showErrors = [];
            foreach ($errors as $error) {
                $showErrors[] = $error->getMessage();
            }

            throw new \RuntimeException(implode('<br/>', $showErrors));
        }
    }

    public static function getSubscribedEvents(): array
    {
        $event = str_replace('Handler', 'Command', static::class);

        return [$event => 'handle'];
    }
}
