<?php

namespace Salt\SiteBundle\Service;

use CftfBundle\Entity\LsDoc;
use CftfBundle\Entity\LsItem;
use CftfBundle\Entity\LsAssociation;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use JMS\DiExtraBundle\Annotation as DI;

/**
 * Class CaseImport.
 *
 * @DI\Service("cftf_import.case")
 */
class CaseImport
{
    /**
     * @var ManagerRegistry
     */
    private $managerRegistry;

    /**
     * CresstCsv constructor
     *
     * @param ManagerRegistry $managerRegistry
     *
     * @DI\InjectParams({
     *     "managerRegistry" = @DI\Inject("doctrine")
     * })
     */
    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * @return ObjectManager
     */
    public function getEntityManager(): ObjectManager
    {
        return $this->managerRegistry->getManagerForClass(LsDoc::class);
    }

    /**
     * Import a CASE file
     *
     * @param \stdClass $fileContent JSON content
     */
    public function importCaseFile(\stdClass $fileContent)
    {
        $em = $this->getEntityManager();
        $lsDoc = new LsDoc();

        $lsDoc->setIdentifier($fileContent->CFDocument->identifier);
        $lsDoc->setUri($fileContent->CFDocument->uri);
        $lsDoc->setCreator($fileContent->CFDocument->creator);
        $lsDoc->setTitle($fileContent->CFDocument->title);
        $lsDoc->setAdoptionStatus($fileContent->CFDocument->adoptionStatus);

        $em->persist($lsDoc);

        $cfItems = $fileContent->CFItems;
        foreach ($cfItems as $cfItem) {
            $lsItem = new LsItem();

            $lsItem->setLsDoc($lsDoc);
            $lsItem->setIdentifier($cfItem->identifier);
            $lsItem->setUri($cfItem->uri);
            $lsItem->setFullStatement($cfItem->fullStatement);
            $lsItem->setListEnumInSource($cfItem->listEnumeration);

            $em->persist($lsItem);
        }

        $cfAssociations = $fileContent->CFAssociations;
        foreach ($cfAssociations as $cfAssociation) {
            $lsAssociation = new LsAssociation();

            $lsAssociation->setLsDoc($lsDoc);
            $lsAssociation->setIdentifier($cfAssociation->identifier);
            $lsAssociation->setUri($cfAssociation->uri);
            $lsAssociation->setOriginNodeIdentifier($cfAssociation->originNodeIdentifier);
            $lsAssociation->setType($cfAssociation->associationType);
            $lsAssociation->setDestinationNodeIdentifier($cfAssociation->destinationNodeIdentifier);

            $em->persist($lsAssociation);
        }

        $em->flush();
    }
}
