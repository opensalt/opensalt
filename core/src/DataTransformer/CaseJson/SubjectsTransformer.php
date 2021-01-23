<?php

namespace App\DataTransformer\CaseJson;

use App\DTO\CaseJson\CFSubject;
use App\Entity\Framework\LsDefSubject;
use App\Repository\Framework\LsDefSubjectRepository;
use Doctrine\ORM\EntityManagerInterface;

class SubjectsTransformer
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
     * @param CFSubject[] $cfSubjects
     *
     * @return LsDefSubject[]
     */
    public function transform(array $cfSubjects): array
    {
        if (0 === count($cfSubjects)) {
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
        /** @var LsDefSubjectRepository $repo */
        $repo = $this->em->getRepository(LsDefSubject::class);

        $newIds = array_map(static function (CFSubject $subject) {
            return $subject->identifier->toString();
        }, $subjects);

        return $repo->findByIdentifiers($newIds);
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
