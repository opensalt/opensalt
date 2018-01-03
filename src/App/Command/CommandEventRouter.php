<?php

namespace App\Command;

use App\Event\CommandEvent;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use JMS\DiExtraBundle\Annotation as DI;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class CommandEventRouter
 *
 * @DI\Service()
 */
class CommandEventRouter
{
    /**
     * @var ManagerRegistry
     */
    private $registry;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * AddDocumentHandler constructor.
     *
     * @DI\InjectParams({
     *     "registry" = @DI\Inject("doctrine"),
     *     "logger" = @DI\Inject("logger"),
     * })
     */
    public function __construct(ManagerRegistry $registry, LoggerInterface $logger)
    {
        $this->registry = $registry;
        $this->em = $registry->getManager();
        $this->logger = $logger;
    }

    /**
     * @DI\Observe(App\Event\CommandEvent::class)
     *
     * @throws \Exception
     */
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        $this->em->getConnection()->beginTransaction();

        /** @var CommandInterface $command */
        $command = $event->getCommand();

        $this->logger->info('Routing command to', ['command' => \get_class($command)]);

        try {
            $dispatcher->dispatch(\get_class($command), $event);

            if ($command->getValidationErrors()) {
                $errorString = (string) $command->getValidationErrors();
                $this->logger->info('Error in command', ['command' => \get_class($command), 'errors' => $errorString]);
            }
        } catch (\Exception $e) {
            $this->logger->info('Exception in command', ['command' => \get_class($command), 'exception' => $e]);

            throw $e;
        }

        $this->em->flush();
        $this->em->getConnection()->commit();

//        $dispatcher->dispatch(AddDocumentEvent::class, new AddDocumentEvent());
    }
}
