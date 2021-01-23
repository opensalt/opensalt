<?php

namespace App\DataTransformer\CaseJson;

use App\DTO\CaseJson\CFItemType;
use App\Entity\Framework\LsDefItemType;
use App\Repository\Framework\LsDefItemTypeRepository;
use Doctrine\ORM\EntityManagerInterface;

class ItemTypesTransformer
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
    }

    /**
     * @param CFItemType[] $cfItemTypes
     *
     * @return LsDefItemType[]
     */
    public function transform(array $cfItemTypes): array
    {
        if (0 === count($cfItemTypes)) {
            return [];
        }

        $existingItemTypes = $this->findExistingItemTypes($cfItemTypes);

        foreach ($cfItemTypes as $cfItemType) {
            $this->updateItemType($cfItemType, $existingItemTypes);
        }

        return $existingItemTypes;
    }

    /**
     * @param CFItemType[] $cfItemTypes
     *
     * @return LsDefItemType[]
     */
    protected function findExistingItemTypes(array $cfItemTypes): array
    {
        /** @var LsDefItemTypeRepository $repo */
        $repo = $this->em->getRepository(LsDefItemType::class);

        $newIds = array_map(static function (CFItemType $itemType) {
            return $itemType->identifier->toString();
        }, $cfItemTypes);

        return $repo->findByIdentifiers($newIds);
    }

    /**
     * @param LsDefItemType[] $existingItemTypes
     */
    protected function updateItemType(CFItemType $cfItemType, array &$existingItemTypes): void
    {
        $type = $this->findOrCreateItemType($cfItemType, $existingItemTypes);
        $type->setUri($cfItemType->uri);
        $type->setTitle($cfItemType->title);
        $type->setDescription($cfItemType->description);
        $type->setCode($cfItemType->typeCode);
        $type->setHierarchyCode($cfItemType->hierarchyCode);
        $type->setChangedAt($cfItemType->lastChangeDateTime);
    }

    /**
     * @param LsDefItemType[] $existingItemTypes
     */
    protected function findOrCreateItemType(CFItemType $cfItemType, array &$existingItemTypes): LsDefItemType
    {
        if (!array_key_exists($cfItemType->identifier->toString(), $existingItemTypes)) {
            $newItemType = new LsDefItemType($cfItemType->identifier->toString());

            $this->em->persist($newItemType);
            $existingItemTypes[$newItemType->getIdentifier()] = $newItemType;
        }

        return $existingItemTypes[$cfItemType->identifier->toString()];
    }
}
