<?php

namespace App\Handler\Framework;

use App\Command\Framework\DeleteItemWithChildrenCommand;
use App\Event\CommandEvent;
use App\Service\FrameworkService;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class DeleteItemWithChildrenHandler
 *
 * @DI\Service()
 */
class DeleteItemWithChildrenHandler
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
     * DeleteItemWithChildrenHandler constructor.
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
     * @DI\Observe(App\Command\Framework\DeleteItemWithChildrenCommand::class)
     *
     * @param CommandEvent $event
     * @param string $eventName
     * @param EventDispatcherInterface $dispatcher
     *
     * @throws \Exception
     */
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var DeleteItemWithChildrenCommand $command */
        $command = $event->getCommand();

        $item = $command->getItem();
        $hasChildren = $item->getChildren();

        $errors = $this->validator->validate($item);
        if (count($errors)) {
            $command->setValidationErrors($errors);
            $errorString = (string) $errors;

            throw new \Exception("Error deleting item: {$errorString}");
        }

        $this->framework->deleteItemWithChildren($item);

//        $dispatcher->dispatch(DeleteItemWithChildrenEvent::class, new DeleteItemWithChildrenEvent());
    }
}
