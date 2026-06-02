<?php

declare(strict_types=1);

namespace App\DataTransformer\CaseJson;

use Doctrine\Persistence\ObjectRepository;

final class UriCollisionResolver
{
    /**
     * Resolve URI collisions within a batch of entities and against existing database records.
     *
     * When two entities share the same URI, the first one to be processed keeps the original URI
     * and subsequent ones are disambiguated by appending their identifier as a URI fragment.
     * Also detects collisions with existing entities in the database that are not part of this batch.
     *
     * @param array<string, object> $entities Entity array indexed by identifier, where each entity has getUri(), setUri(), and getIdentifier() methods
     * @param ObjectRepository<object> $repository Repository for the entity type, used to check for existing DB collisions
     */
    public static function resolve(array $entities, ObjectRepository $repository): void
    {
        $seenUris = [];
        $batchIdentifiers = [];

        // Collect all identifiers in the batch for DB collision exclusion
        foreach ($entities as $entity) {
            $batchIdentifiers[$entity->getIdentifier()] = true;
        }

        foreach ($entities as $entity) {
            $uri = $entity->getUri();
            $identifier = $entity->getIdentifier();

            if (isset($seenUris[$uri])) {
                $entity->setUri($uri . '#' . $identifier);
                $seenUris[$uri . '#' . $identifier] = true;
                continue;
            }

            $existing = $repository->findOneBy(['uri' => $uri]);
            if (null !== $existing && !isset($batchIdentifiers[$existing->getIdentifier()])) {
                $entity->setUri($uri . '#' . $identifier);
                $seenUris[$uri] = true;
                $seenUris[$uri . '#' . $identifier] = true;
                continue;
            }

            $seenUris[$uri] = true;
        }
    }
}
