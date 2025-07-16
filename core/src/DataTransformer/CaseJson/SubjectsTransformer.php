<?php

declare(strict_types=1);

namespace App\DataTransformer\CaseJson;

use App\DTO\CaseJson\CFSubject;
use App\Entity\Framework\LsDefSubject;
use App\Repository\Framework\LsDefSubjectRepository;
use Doctrine\ORM\EntityManagerInterface;

final readonly class SubjectsTransformer
{
    public function __construct(
        private EntityManagerInterface $em,
        private LsDefSubjectRepository $repository,
    ) {
    }

    /**
     * @param CFSubject[] $cfSubjects
     *
     * @return LsDefSubject[]
     */
    public function transform(array $cfSubjects): array
    {
        if ([] === $cfSubjects) {
            return [];
        }

        $existingSubjects = $this->findExistingSubjects($cfSubjects);

        foreach ($cfSubjects as $cfItemType) {
            $this->updateSubject($cfItemType, $existingSubjects);
        }

        return $existingSubjects;
    }

    /**
     * @param CFSubject[] $subjects
     *
     * @return LsDefSubject[]
     */
    protected function findExistingSubjects(array $subjects): array
    {
        $newIds = array_map(static fn (CFSubject $subject): string => $subject->identifier->toString(), $subjects);

        return $this->repository->findByIdentifiers($newIds);
    }

    /**
     * @param LsDefSubject[] $existingSubjects
     */
    protected function updateSubject(CFSubject $cfSubject, array &$existingSubjects): void
    {
        $subject = $this->findOrCreateSubject($cfSubject, $existingSubjects);
        $subject->setUri($cfSubject->uri);
        $subject->setTitle($cfSubject->title);
        $subject->setDescription($cfSubject->description);
        $subject->setHierarchyCode($cfSubject->hierarchyCode);
        $subject->setChangedAt($cfSubject->lastChangeDateTime);
        $subject->setExtensions($cfSubject->extensions);
    }

    /**
     * @param LsDefSubject[] $existingSubjects
     */
    protected function findOrCreateSubject(CFSubject $cfSubject, array &$existingSubjects): LsDefSubject
    {
        if (!array_key_exists($cfSubject->identifier->toString(), $existingSubjects)) {
            $subject = new LsDefSubject($cfSubject->identifier->toString());

            $this->em->persist($subject);
            $existingSubjects[$subject->getIdentifier()] = $subject;
        }

        return $existingSubjects[$cfSubject->identifier->toString()];
    }
}
