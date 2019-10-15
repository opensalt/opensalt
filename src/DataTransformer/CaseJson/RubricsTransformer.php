<?php

namespace App\DataTransformer\CaseJson;

use App\DTO\CaseJson\CFRubric as CFPackageRubric;
use App\DTO\CaseJson\CFRubricCriterion as CFPackageCriterion;
use App\DTO\CaseJson\CFRubricCriterionLevel as CFPackageCriterionLevel;
use App\Entity\Framework\CfRubricCriterion;
use App\Entity\Framework\CfRubricCriterionLevel;
use App\Entity\Framework\LsItem;
use App\Entity\Framework\CfRubric;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class RubricsTransformer
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var LsItem[]|array
     */
    private $items;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        $this->em = $entityManager;
        $this->logger = $logger;
    }

    /**
     * @param CFPackageRubric[] $cfRubrics
     * @param LsItem[] $items
     * @return CfRubric[]
     */
    public function transform(array $cfRubrics, array $items): array
    {
        $this->items = $items;

        $rubrics = $this->findExistingRubrics($cfRubrics);

        foreach ($cfRubrics as $cfRubric) {
            $identifier = $cfRubric->identifier->toString();
            $rubric = $rubrics[$identifier] ?? $this->createRubric($cfRubric);
            $rubrics[$identifier] = $this->updateRubric($rubric, $cfRubric);
        }

        // We can't remove any rubrics as we don't have a way to ensure they are no longer needed anywhere

        return $rubrics;
    }

    /**
     * @param CFPackageRubric[] $cfRubrics
     * @return CfRubric[]
     */
    private function findExistingRubrics(array $cfRubrics): array
    {
        $identifiers = array_map(static function (CFPackageRubric $rubric) {
            return $rubric->identifier->toString();
        }, $cfRubrics);

        return $this->em->getRepository(CfRubric::class)->findByIdentifiers($identifiers);
    }

    private function createRubric(CFPackageRubric $cfRubric): CfRubric
    {
        $rubric = new CfRubric($cfRubric->identifier);
        $this->em->persist($rubric);

        return $rubric;
    }

    private function updateRubric(CfRubric $rubric, CFPackageRubric $cfRubric): CfRubric
    {
        $rubric->setUri($cfRubric->uri);
        $rubric->setTitle($cfRubric->title);
        $rubric->setDescription($cfRubric->description);
        $rubric->setUpdatedAt($cfRubric->lastChangeDateTime);

        $this->updateCriteria($rubric, $cfRubric->cfRubricCriteria);

        return $rubric;
    }

    /**
     * @param CfRubric $rubric
     * @param CFPackageCriterion[] $cfCriteria
     */
    private function updateCriteria(CfRubric $rubric, array $cfCriteria): void
    {
        $newCriteria = array_combine(array_map(static function ($cfCriterion) {
            return $cfCriterion->identifier->toString();
        }, $cfCriteria), $cfCriteria);

        $tmpCriteria = $rubric->getCriteria()->toArray();
        /** @var CfRubricCriterion[] $existingCriteria */
        $existingCriteria = array_combine(array_map(static function (CfRubricCriterion $criterion) {
            return $criterion->getIdentifier();
        }, $tmpCriteria), $tmpCriteria);
        $tmpCriteria = null;

        $criteria = [];
        foreach ($newCriteria as $cfCriterion) {
            $identifier = $cfCriterion->identifier->toString();
            $criterion = $existingCriteria[$identifier] ?? $this->createCriterion($cfCriterion, $rubric);
            $criteria[$identifier] = $this->updateCriterion($criterion, $cfCriterion);
        }

        $rubric->setCriteria($criteria);
    }

    private function createCriterion(CFPackageCriterion $cfCriterion, CfRubric $rubric): CfRubricCriterion
    {
        $criterion = new CfRubricCriterion($cfCriterion->identifier->toString());
        $criterion->setRubric($rubric);
        $this->em->persist($criterion);

        return $criterion;
    }

    private function updateCriterion(CfRubricCriterion $criterion, CFPackageCriterion $cfCriterion): CfRubricCriterion
    {
        if ($criterion->getRubric()->getIdentifier() !== $cfCriterion->rubricId) {
            $this->logger->error(sprintf('Attempt to change the rubric from %s to %s of criterion %s', $criterion->getRubric()->getIdentifier(), $cfCriterion->rubricId, $cfCriterion->identifier->toString()));

            throw new \UnexpectedValueException('Cannot change the rubric of a criterion');
        }

        $criterion->setUri($cfCriterion->uri);
        $criterion->setDescription($cfCriterion->description);
        $criterion->setUpdatedAt($cfCriterion->lastChangeDateTime);
        $criterion->setCategory($cfCriterion->category);
        $criterion->setPosition($cfCriterion->position);
        $criterion->setWeight($cfCriterion->weight);

        $itemIdentifier = $cfCriterion->cfItemURI->identifier->toString();
        $criterion->setItem($this->items[$itemIdentifier] ?? $this->findItem($itemIdentifier));

        $this->updateLevels($criterion, $cfCriterion->cfRubricCriterionLevels);

        return $criterion;
    }

    /**
     * @param CFPackageCriterionLevel[] $cfRubricCriterionLevels
     * @return CfRubricCriterionLevel[]
     */
    private function updateLevels(CfRubricCriterion $criterion, array $cfRubricCriterionLevels): array
    {
        $newLevels = array_combine(array_map(static function ($cfCriterionLevel) {
            return $cfCriterionLevel->identifier->toString();
        }, $cfRubricCriterionLevels), $cfRubricCriterionLevels);

        $tmpLevels = $criterion->getLevels()->toArray();
        $existingLevels = array_combine(array_map(static function (CfRubricCriterionLevel $level) {
            return $level->getIdentifier();
        }, $tmpLevels), $tmpLevels);
        $tmpLevels = null;

        $levels = [];
        foreach ($newLevels as $cfCriterionLevel) {
            $identifier = $cfCriterionLevel->identifier->toString();
            $level = $existingLevels[$identifier] ?? $this->createCriterionLevel($cfCriterionLevel, $criterion);
            $levels[$identifier] = $this->updateCriterionLevel($level, $cfCriterionLevel);
        }

        $criterion->setLevels($levels);

        return $levels;
    }

    private function createCriterionLevel(CFPackageCriterionLevel $cfCriterionLevel, CfRubricCriterion $criterion): CfRubricCriterionLevel
    {
        $level = new CfRubricCriterionLevel($cfCriterionLevel->identifier->toString());
        $level->setCriterion($criterion);
        $this->em->persist($level);

        return $level;
    }

    private function updateCriterionLevel(CfRubricCriterionLevel $level, CFPackageCriterionLevel $cfCriterionLevel): CfRubricCriterionLevel
    {
        if ($level->getCriterion()->getIdentifier() !== $cfCriterionLevel->rubricCriterionId) {
            $this->logger->error(sprintf('Attempt to change the criterion from %s to %s of criterion level %s', $cfCriterionLevel->rubricCriterionId, $level->getCriterion()->getIdentifier(), $cfCriterionLevel->identifier->toString()));

            throw new \UnexpectedValueException('Cannot change the criterion of a criterion level');
        }

        $level->setUri($cfCriterionLevel->uri);
        $level->setDescription($cfCriterionLevel->description);
        $level->setPosition($cfCriterionLevel->position);
        $level->setFeedback($cfCriterionLevel->feedback);
        $level->setQuality($cfCriterionLevel->quality);
        $level->setScore($cfCriterionLevel->score);
        $level->setUpdatedAt($cfCriterionLevel->lastChangeDateTime);

        return $level;
    }

    private function findItem(string $itemIdentifier): LsItem
    {
        $item = $this->em->getRepository(LsItem::class)->findOneByIdentifier($itemIdentifier);

        if (null === $item) {
            $this->logger->error(sprintf('Item %s for CFRubricCriterion is missing', $itemIdentifier));

            throw new \UnexpectedValueException(sprintf('Cannot find item %s for CFRubricCriterion', $itemIdentifier));
        }

        return $item;
    }
}
