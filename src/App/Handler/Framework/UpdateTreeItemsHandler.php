<?php

namespace App\Handler\Framework;

use App\Command\Framework\UpdateTreeItemsCommand;
use App\Event\CommandEvent;
use App\Service\FrameworkService;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class UpdateTreeItemsHandler
 *
 * @DI\Service()
 */
class UpdateTreeItemsHandler
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
     * UpdateTreeItemsHandler constructor.
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
     * @DI\Observe(App\Command\Framework\UpdateTreeItemsCommand::class)
     *
     * @param CommandEvent $event
     * @param string $eventName
     * @param EventDispatcherInterface $dispatcher
     *
     * @throws \Exception
     */
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var UpdateTreeItemsCommand $command */
        $command = $event->getCommand();

        $doc = $command->getDoc();
        $items = $command->getItems();

        $errors = $this->validator->validate($command);
        if (count($errors)) {
            $command->setValidationErrors($errors);
            $errorString = (string) $errors;

            throw new \Exception("Error updating items: {$errorString}");
        }

        $ret = $this->framework->updateTreeItems($doc, $items);
        $command->setReturnValues($ret);

//        $dispatcher->dispatch(UpdateTreeItemsEvent::class, new UpdateTreeItemsEvent());
    }
}
