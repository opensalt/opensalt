<?php

namespace App\Handler\Framework;

use App\Command\Framework\DeleteItemCommand;
use App\Event\CommandEvent;
use App\Service\FrameworkService;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class DeleteItemHandler
 *
 * @DI\Service()
 */
class DeleteItemHandler
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
     * DeleteItemHandler constructor.
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
     * @DI\Observe(App\Command\Framework\DeleteItemCommand::class)
     *
     * @param CommandEvent $event
     * @param string $eventName
     * @param EventDispatcherInterface $dispatcher
     *
     * @throws \Exception
     */
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var DeleteItemCommand $command */
        $command = $event->getCommand();

        $item = $command->getItem();
        $hasChildren = $item->getChildren();

        if ($hasChildren->isEmpty()) {
            throw new \Exception('Cannot delete an item with children.');
        }

        $errors = $this->validator->validate($item);
        if (count($errors)) {
            $command->setValidationErrors($errors);
            $errorString = (string) $errors;

            throw new \Exception("Error deleting item: {$errorString}");
        }

        $this->framework->deleteItem($item);

//        $dispatcher->dispatch(DeleteItemEvent::class, new DeleteItemEvent());
    }
}
