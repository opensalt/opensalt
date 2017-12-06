<?php

namespace App\Handler\Framework;

use App\Command\Framework\AddExemplarToItemCommand;
use App\Event\CommandEvent;
use App\Service\FrameworkService;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class AddExemplarToItemHandler
 *
 * @DI\Service()
 */
class AddExemplarToItemHandler
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
     * AddExemplarToItemHandler constructor.
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
     * @DI\Observe(App\Command\Framework\AddExemplarToItemCommand::class)
     *
     * @param CommandEvent $event
     * @param string $eventName
     * @param EventDispatcherInterface $dispatcher
     *
     * @throws \Exception
     */
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var AddExemplarToItemCommand $command */
        $command = $event->getCommand();

        $item = $command->getItem();
        $url = $command->getUrl();

        $errors = $this->validator->validate($item);
        if (count($errors)) {
            $command->setValidationErrors($errors);
            $errorString = (string) $errors;

            throw new \Exception("Error adding exemplar: {$errorString}");
        }

        $association = $this->framework->addExemplarToItem($item, $url);
        $command->setAssociation($association);

//        $dispatcher->dispatch(AddExemplarToItemEvent::class, new AddExemplarToItemEvent());
    }
}
