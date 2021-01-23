<?php

namespace App\DataTransformer\CaseJson;

use App\DTO\CaseJson\CFConcept;
use App\Entity\Framework\LsDefConcept;
use App\Repository\Framework\LsDefConceptRepository;
use Doctrine\ORM\EntityManagerInterface;

class ConceptsTransformer
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
     * @param CFConcept[] $cfConcepts
     *
     * @return LsDefConcept[]
     */
    public function transform(array $cfConcepts): array
    {
        if (0 === count($cfConcepts)) {
            return [];
        }

        $existingConcepts = $this->findExistingConcepts($cfConcepts);

        foreach ($cfConcepts as $cfConcept) {
            $this->updateConcept($cfConcept, $existingConcepts);
        }

        return $existingConcepts;
    }

    /**
     * @param CFConcept[] $cfConcepts
     *
     * @return LsDefConcept[]
     */
    protected function findExistingConcepts(array $cfConcepts): array
    {
        /** @var LsDefConceptRepository $repo */
        $repo = $this->em->getRepository(LsDefConcept::class);

        $newIds = array_map(static function (CFConcept $itemType) {
            return $itemType->identifier->toString();
        }, $cfConcepts);

        return $repo->findByIdentifiers($newIds);
    }

    /**
     * @param LsDefConcept[] $existingConcepts
     */
    protected function updateConcept(CFConcept $cfConcept, array &$existingConcepts): void
    {
        $concept = $this->findOrCreateConcept($cfConcept, $existingConcepts);
        $concept->setUri($cfConcept->uri);
        $concept->setTitle($cfConcept->title);
        $concept->setDescription($cfConcept->description);
        $concept->setHierarchyCode($cfConcept->hierarchyCode);
        $concept->setChangedAt($cfConcept->lastChangeDateTime);
        $concept->setKeywords($cfConcept->keywords);
    }

    /**
     * @param LsDefConcept[] $existingConcepts
     */
    protected function findOrCreateConcept(CFConcept $cfConcept, array &$existingConcepts): LsDefConcept
    {
        if (!array_key_exists($cfConcept->identifier->toString(), $existingConcepts)) {
            $newConcept = new LsDefConcept($cfConcept->identifier->toString());

            $this->em->persist($newConcept);
            $existingConcepts[$newConcept->getIdentifier()] = $newConcept;
        }

        return $existingConcepts[$cfConcept->identifier->toString()];
    }
}
