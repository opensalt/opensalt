<?php

namespace App\Handler\Framework;

use App\Command\Framework\AddItemCommand;
use App\Event\CommandEvent;
use App\Service\FrameworkService;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class AddItemHandler
 *
 * @DI\Service()
 */
class AddItemHandler
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
     * AddItemHandler constructor.
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
     * @DI\Observe(App\Command\Framework\AddItemCommand::class)
     *
     * @param CommandEvent $event
     * @param string $eventName
     * @param EventDispatcherInterface $dispatcher
     *
     * @throws \Exception
     */
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var AddItemCommand $command */
        $command = $event->getCommand();

        $item = $command->getItem();

        if ($command->getParent()) {
            $command->getParent()->addChild($item, $command->getAssocGroup());
        } else {
            $command->getDoc()->addTopLsItem($item, $command->getAssocGroup());
        }

        $errors = $this->validator->validate($item);
        if (count($errors)) {
            $command->setValidationErrors($errors);
            $errorString = (string) $errors;

            throw new \Exception("Error creating item: {$errorString}");
        }

        $this->framework->addItem($item);

//        $dispatcher->dispatch(AddItemEvent::class, new AddItemEvent());
    }
}
