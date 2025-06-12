<?php

declare(strict_types=1);

namespace App\DataTransformer\CaseJson;

use App\DTO\CaseJson\CFPackageDocument;
use App\DTO\CaseJson\Definitions;
use App\Entity\Framework\FrameworkType;
use App\Entity\Framework\LsDoc;
use Doctrine\ORM\EntityManagerInterface;

class DocumentTransformer
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    public function transform(CFPackageDocument $cfDocument, Definitions $definitions): LsDoc
    {
        $doc = $this->findOrCreateDocument($cfDocument);

        $this->updateAssociationGroups($definitions->associationGroupings, $doc);

        return $this->updateDocument($doc, $cfDocument, $definitions);
    }

    private function findOrCreateDocument(CFPackageDocument $cfDocument): LsDoc
    {
        $doc = $this->em->getRepository(LsDoc::class)->findOneByIdentifier($cfDocument->identifier->toString());

        if (null === $doc) {
            $doc = new LsDoc($cfDocument->identifier->toString());
            $this->em->persist($doc);
        }

        return $doc;
    }

    private function findOrCreateFrameworkType(?string $frameworkType): ?FrameworkType
    {
        if (null === $frameworkType) {
            return null;
        }

        $frameworkTypeObj = $this->em->getRepository(FrameworkType::class)->findOneBy(['frameworkType' => $frameworkType]);

        if (null === $frameworkTypeObj) {
            $frameworkTypeObj = new FrameworkType($frameworkType);
            $this->em->persist($frameworkTypeObj);
        }

        return $frameworkTypeObj;
    }

    private function updateDocument(LsDoc $doc, CFPackageDocument $cfDocument, Definitions $definitions): LsDoc
    {
        $doc->setUri($cfDocument->uri);
        $doc->setCaseVersion($cfDocument->caseVersion);
        $doc->setFrameworkType($this->findOrCreateFrameworkType($cfDocument->frameworkType));
        $doc->setTitle($cfDocument->title);
        $doc->setDescription($cfDocument->description);
        $doc->setAdoptionStatus($cfDocument->adoptionStatus);
        $doc->setLanguage($cfDocument->language);
        $doc->setNote($cfDocument->notes);
        $doc->setOfficialUri($cfDocument->officialSourceURL);
        $doc->setPublisher($cfDocument->publisher);
        $doc->setCreator($cfDocument->creator);
        $doc->setSubject($cfDocument->subject);
        $doc->setStatusStart($cfDocument->statusStartDate);
        $doc->setStatusEnd($cfDocument->statusEndDate);
        $doc->setVersion($cfDocument->version);
        $doc->setChangedAt($cfDocument->lastChangeDateTime);
        $doc->setExtensions($cfDocument->extensions);

        if (null !== $cfDocument->licenseURI) {
            $licence = $definitions->licences[$cfDocument->licenseURI->identifier->toString()] ?? null;
            $doc->setLicence($licence);
        }

        $doc->setSubjects(null);
        foreach ($cfDocument->subjectURI ?? [] as $subjectUri) {
            $subject = $definitions->subjects[$subjectUri->identifier->toString()] ?? null;
            if (null !== $subject) {
                $doc->addSubject($subject);
            }
        }

        return $doc;
    }

    private function updateAssociationGroups(array $associationGroupings, LsDoc $doc): void
    {
        foreach ($associationGroupings as $group) {
            if (null === $group->getLsDoc()) {
                $group->setLsDoc($doc);
            }
        }
    }
}
