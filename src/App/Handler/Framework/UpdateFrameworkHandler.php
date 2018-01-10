<?php

namespace App\Handler\Framework;

use App\Command\Framework\UpdateFrameworkCommand;
use App\Event\CommandEvent;
use App\Event\NotificationEvent;
use App\Handler\AbstractDoctrineHandler;
use CftfBundle\Service\FrameworkUpdater;
use Doctrine\Common\Persistence\ManagerRegistry;
use JMS\DiExtraBundle\Annotation as DI;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class UpdateFrameworkHandler
 *
 * @DI\Service()
 */
class UpdateFrameworkHandler extends AbstractDoctrineHandler
{
    /**
     * @var FrameworkUpdater
     */
    private $frameworkUpdater;

    /**
     * @DI\InjectParams({
     *     "validator" = @DI\Inject("validator"),
     *     "registry" = @DI\Inject("doctrine"),
     *     "frameworkUpdater" = @DI\Inject(CftfBundle\Service\FrameworkUpdater::class)
     * })
     */
    public function __construct(ValidatorInterface $validator, ManagerRegistry $registry, FrameworkUpdater $frameworkUpdater)
    {
        parent::__construct($validator, $registry);
        $this->frameworkUpdater = $frameworkUpdater;
    }

    /**
     * @DI\Observe(App\Command\Framework\UpdateFrameworkCommand::class)
     *
     * @param CommandEvent $event
     * @param string $eventName
     * @param EventDispatcherInterface $dispatcher
     *
     * @throws \Exception
     */
    public function handle(CommandEvent $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        /** @var UpdateFrameworkCommand $command */
        $command = $event->getCommand();
        $this->validate($command, $command);

        $doc = $command->getDoc();
        $fileContent = $command->getFileContent();
        $frameworkToAssociate = $command->getFrameworkToAssociate();
        $cfItemKeys = $command->getCfItemKeys();

        $this->frameworkUpdater->update($doc, $fileContent, $frameworkToAssociate, $cfItemKeys);

        $notification = new NotificationEvent(
            'D07',
            'Framework document updated',
            $doc,
            [
                'doc-u' => [
                    $doc->getId() => $doc->getIdentifier(),
                ],
            ]
        );
        $command->setNotificationEvent($notification);
    }
}
