<?php

namespace App\Handler;

use App\Command\CommandInterface;
use App\Event\CommandEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

abstract class BaseValidatedHandler
{
    /**
     * @var ValidatorInterface
     */
    protected $validator;

    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    abstract public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void;

    public function validate(CommandInterface $command, $toValidate): void
    {
        $errors = $this->validator->validate($toValidate);
        if (\count($errors)) {
            $command->setValidationErrors($errors);

            throw new \RuntimeException((string) $errors);
        }
    }
}
