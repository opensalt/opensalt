<?php

declare(strict_types=1);

namespace App\VectorSearch\EventListener;

use App\Entity\Framework\LsItem;
use App\VectorSearch\Message\GenerateEmbeddingMessage;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsEntityListener(event: Events::postPersist, entity: LsItem::class)]
#[AsEntityListener(event: Events::postUpdate, entity: LsItem::class)]
readonly class LsItemEmbeddingListener
{
    public function __construct(
        private MessageBusInterface $messageBus,
    ) {
    }

    public function postPersist(LsItem $entity, PostPersistEventArgs $args): void
    {
        // Dispatch message for async embedding generation
        $this->messageBus->dispatch(new GenerateEmbeddingMessage($entity->getId()));
    }

    public function postUpdate(LsItem $entity, PostUpdateEventArgs $args): void
    {
        // Check if fullStatement changed
        $changeSet = $args->getObjectManager()->getUnitOfWork()->getEntityChangeSet($entity);

        if (isset($changeSet['fullStatement'])) {
            // Dispatch message for async embedding regeneration
            $this->messageBus->dispatch(new GenerateEmbeddingMessage(
                $entity->getId(),
                null,
                true // Force regeneration
            ));
        }
    }
}
