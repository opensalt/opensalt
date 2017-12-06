<?php

namespace App\Handler\Framework;

use App\Command\Framework\AddTreeAssociationCommand;
use App\Event\CommandEvent;
use App\Service\FrameworkService;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class AddTreeAssociationHandler
 *
 * @DI\Service()
 */
class AddTreeAssociationHandler
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
     * AddTreeAssociationHandler constructor.
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
     * @DI\Observe(App\Command\Framework\AddTreeAssociationCommand::class)
     *
     * @param CommandEvent $event
     * @param string $eventName
     * @param EventDispatcherInterface $dispatcher
     *
     * @throws \Exception
     */
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var AddTreeAssociationCommand $command */
        $command = $event->getCommand();

        $doc = $command->getDoc();
        $type = $command->getType();
        $origin = $command->getOrigin();
        $dest = $command->getDestination();
        $assocGroup = $command->getAssocGroup();

        $errors = $this->validator->validate($command);
        if (count($errors)) {
            $command->setValidationErrors($errors);
            $errorString = (string) $errors;

            throw new \Exception("Error adding association: {$errorString}");
        }

        $association = $this->framework->addTreeAssociation($doc, $origin, $type, $dest, $assocGroup);
        $command->setAssociation($association);

//        $dispatcher->dispatch(AddTreeAssociationEvent::class, new AddTreeAssociationEvent());
    }
}
