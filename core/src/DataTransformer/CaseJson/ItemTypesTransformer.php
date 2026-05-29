<?php

declare(strict_types=1);

namespace App\DataTransformer\CaseJson;

use App\DTO\CaseJson\CFItemType;
use App\Entity\Framework\LsDefItemType;
use App\Repository\Framework\LsDefItemTypeRepository;
use Doctrine\ORM\EntityManagerInterface;

final readonly class ItemTypesTransformer
{
    public function __construct(
        private EntityManagerInterface $em,
        private LsDefItemTypeRepository $repository,
    ) {
    }

    /**
     * @param CFItemType[] $cfItemTypes
     *
     * @return LsDefItemType[]
     */
    public function transform(array $cfItemTypes): array
    {
        if ([] === $cfItemTypes) {
            return [];
        }

        $existingItemTypes = $this->findExistingItemTypes($cfItemTypes);

        foreach ($cfItemTypes as $cfItemType) {
            $this->updateItemType($cfItemType, $existingItemTypes);
        }

        UriCollisionResolver::resolve($existingItemTypes, $this->repository);

        return $existingItemTypes;
    }

    /**
     * @param CFItemType[] $cfItemTypes
     *
     * @return LsDefItemType[]
     */
    private function findExistingItemTypes(array $cfItemTypes): array
    {
        $newIds = array_map(static fn (CFItemType $itemType): string => $itemType->identifier->toString(), $cfItemTypes);

        return $this->repository->findByIdentifiers($newIds);
    }

    /**
     * @param LsDefItemType[] $existingItemTypes
     */
    private function updateItemType(CFItemType $cfItemType, array &$existingItemTypes): void
    {
        $type = $this->findOrCreateItemType($cfItemType, $existingItemTypes);
        $type->setUri($cfItemType->uri);
        $type->setTitle($cfItemType->title);
        // Substitute title if description does not exist (as it is required)
        //  - Added as CPALMS does not have description in their payload
        $type->setDescription($cfItemType->description ?? $cfItemType->title);
        $type->setCode($cfItemType->typeCode);
        $type->setHierarchyCode($cfItemType->hierarchyCode);
        $type->setChangedAt($cfItemType->lastChangeDateTime);
        $type->setExtensions($cfItemType->extensions);
    }

    /**
     * @param LsDefItemType[] $existingItemTypes
     */
    private function findOrCreateItemType(CFItemType $cfItemType, array &$existingItemTypes): LsDefItemType
    {
        if (!array_key_exists($cfItemType->identifier->toString(), $existingItemTypes)) {
            $newItemType = new LsDefItemType($cfItemType->identifier->toString());

            $this->em->persist($newItemType);
            $existingItemTypes[$newItemType->getIdentifier()] = $newItemType;
        }

        return $existingItemTypes[$cfItemType->identifier->toString()];
    }
}
