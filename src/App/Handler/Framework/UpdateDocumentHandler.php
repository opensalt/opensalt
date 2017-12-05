<?php

namespace App\Handler\Framework;

use App\Command\Framework\AddDocumentCommand;
use App\Event\CommandEvent;
use App\Service\FrameworkService;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class UpdateDocumentHandler
 *
 * @DI\Service()
 */
class UpdateDocumentHandler
{
    /**
     * @var FrameworkService
     */
    private $framework;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * AddDocumentHandler constructor.
     *
     * @DI\InjectParams({
     *     "validator" = @DI\Inject("validator"),
     *     "framework" = @DI\Inject(App\Service\FrameworkService::class)
     * })
     *
     * @param ValidatorInterface $validator
     * @param FrameworkService $framework
     */
    public function __construct(ValidatorInterface $validator, FrameworkService $framework)
    {
        $this->validator = $validator;
        $this->framework = $framework;
    }

    /**
     * @DI\Observe(App\Command\Framework\AddDocumentCommand::class)
     *
     * @param CommandEvent $event
     * @param string $eventName
     * @param EventDispatcherInterface $dispatcher
     *
     * @throws \Exception
     */
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var AddDocumentCommand $command */
        $command = $event->getCommand();

        $doc = $command->getDoc();

        $errors = $this->validator->validate($doc);
        if (count($errors)) {
            $command->setValidationErrors($errors);
            $errorString = (string) $errors;

            throw new \Exception("Error updating framework: {$errorString}");
        }

        $this->framework->updateDocument($doc);

//        $dispatcher->dispatch(AddDocumentEvent::class, new UpdateDocumentEvent());
    }
}
