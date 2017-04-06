<?php

namespace Salt\SiteBundle\Service;

use CftfBundle\Entity\LsDoc;
use CftfBundle\Entity\LsItem;
use CftfBundle\Entity\LsAssociation;
use Doctrine\ORM\EntityManager;
use JMS\DiExtraBundle\Annotation as DI;

/**
 * Class CaseImport.
 *
 * @DI\Service("cftf_import.case")
 */
class CaseImport
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @param EntityManager $em
     *
     * @DI\InjectParams({
     *     "em" = @DI\Inject("doctrine.orm.entity_manager")
     * })
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * @return EntityManager
     */
    public function getEntityManager()
    {
        return $this->em;
    }

    /**
     * Import a CASE file
     *
     * @param json $fileContent
     */
    public function importCaseFile($fileContent)
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
        for ($i = 0; $i < count($cfItems); $i++) {
            $cfItem = $cfItems[$i];
            $lsItem = new LsItem();

            $lsItem->setLsDoc($lsDoc);
            $lsItem->setIdentifier($cfItem->identifier);
            $lsItem->setUri($cfItem->uri);
            $lsItem->setFullStatement($cfItem->fullStatement);
            $lsItem->setListEnumInSource($cfItem->listEnumeration);

            $em->persist($lsItem);
        }

        $cfAssociations = $fileContent->CFAssociations;
        for ($i = 0; $i < count($cfAssociations); $i++) {
            $cfAssociation = $cfAssociations[$i];
            $lsAssociation = new LsAssociation;

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
