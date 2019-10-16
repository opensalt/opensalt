<?php

namespace App\DataTransformer\CaseJson;

use App\DTO\CaseJson\CFPackageDocument;
use App\DTO\CaseJson\Definitions;
use App\Entity\Framework\LsDoc;
use Doctrine\ORM\EntityManagerInterface;

class DocumentTransformer
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var Definitions
     */
    private $definitions;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
    }

    public function transform(CFPackageDocument $cfDocument, Definitions $definitions): LsDoc
    {
        $this->definitions = $definitions;
        $doc = $this->findOrCreateDocument($cfDocument);

        return $this->updateDocument($doc, $cfDocument);
    }

    private function findOrCreateDocument(CFPackageDocument $cfDocument): LsDoc
    {
        $doc = $this->em->getRepository(LsDoc::class)->findOneByIdentifier($cfDocument->identifier->toString());

        if (null === $doc) {
            $doc = new LsDoc($cfDocument->identifier);
            $this->em->persist($doc);
        }

        return $doc;
    }

    private function updateDocument(LsDoc $doc, CFPackageDocument $cfDocument): LsDoc
    {
        $doc->setUri($cfDocument->uri);
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
        $doc->setUpdatedAt($cfDocument->lastChangeDateTime);

        if (null !== $cfDocument->licenseURI) {
            $licence = $this->definitions->licences[$cfDocument->licenseURI->identifier->toString()] ?? null;
            $doc->setLicence($licence);
        }

        $doc->setSubjects(null);
        foreach ($cfDocument->subjectURI as $subjectUri) {
            $subject = $this->definitions->subjects[$subjectUri->identifier->toString()] ?? null;
            if (null !== $subject) {
                $doc->addSubject($subject);
            }
        }

        return $doc;
    }
}
