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

        if (property_exists($fileContent->CFDocument, 'identifier')) $lsDoc->setIdentifier($fileContent->CFDocument->identifier);
        if (property_exists($fileContent->CFDocument, 'uri')) $lsDoc->setUri($fileContent->CFDocument->uri);
        if (property_exists($fileContent->CFDocument, 'creator')) $lsDoc->setCreator($fileContent->CFDocument->creator);
        if (property_exists($fileContent->CFDocument, 'publisher')) $lsDoc->setPublisher($fileContent->CFDocument->publisher);
        if (property_exists($fileContent->CFDocument, 'title')) $lsDoc->setTitle($fileContent->CFDocument->title);
        if (property_exists($fileContent->CFDocument, 'notes')) $lsDoc->setNote($fileContent->CFDocument->notes);
        if (property_exists($fileContent->CFDocument, 'officialSourceURL')) $lsDoc->setOfficialUri($fileContent->CFDocument->officialSourceURL);
        if (property_exists($fileContent->CFDocument, 'version')) $lsDoc->setVersion($fileContent->CFDocument->version);
        if (property_exists($fileContent->CFDocument, 'description')) $lsDoc->setDescription($fileContent->CFDocument->description);
        if (property_exists($fileContent->CFDocument, 'language')) $lsDoc->setLanguage($fileContent->CFDocument->language);

        $em->persist($lsDoc);

        $cfItems = $fileContent->CFItems;
        $items = [];
        $items[$lsDoc->getIdentifier()] = $lsDoc;
        foreach ($cfItems as $cfItem) {
            $lsItem = new LsItem();

            $lsItem->setLsDoc($lsDoc);
            if (property_exists($cfItem, 'identifier')) $lsItem->setIdentifier($cfItem->identifier);
            if (property_exists($cfItem, 'uri')) $lsItem->setUri($cfItem->uri);
            if (property_exists($cfItem, 'fullStatement')) $lsItem->setFullStatement($cfItem->fullStatement);
            if (property_exists($cfItem, 'listEnumeration')) $lsItem->setListEnumInSource($cfItem->listEnumeration);
            if (property_exists($cfItem, 'humanCodingScheme')) $lsItem->setHumanCodingScheme($cfItem->humanCodingScheme);
            if (property_exists($cfItem, 'abbreviatedStatement')) $lsItem->setAbbreviatedStatement($cfItem->abbreviatedStatement);
            if (property_exists($cfItem, 'notes')) $lsItem->setNotes($cfItem->notes);
            if (property_exists($cfItem, 'educationAlignment')) $lsItem->setEducationalAlignment($cfItem->educationAlignment);
            if (property_exists($cfItem, 'language')) $lsItem->setLanguage($cfItem->language);

            $em->persist($lsItem);
            $items[$cfItem->identifier] = $lsItem;
        }

        $cfAssociations = $fileContent->CFAssociations;
        foreach ($cfAssociations as $cfAssociation) {
            $lsAssociation = new LsAssociation();

            $lsAssociation->setLsDoc($lsDoc);
            if (property_exists($cfAssociation, 'identifier')) $lsAssociation->setIdentifier($cfAssociation->identifier);
            if (property_exists($cfAssociation, 'uri')) $lsAssociation->setUri($cfAssociation->uri);
            if (property_exists($cfAssociation, 'associationType')) $lsAssociation->setType($cfAssociation->associationType);
            if (property_exists($cfAssociation, 'groupName')) $lsAssociation->setGroupName($cfAssociation->groupName);

            if (property_exists($cfAssociation, 'originNodeURI') && is_object($cfAssociation->originNodeURI)) {
                if (array_key_exists($cfAssociation->originNodeURI->identifier, $items)) {
                    $lsAssociation->setOrigin($items[$cfAssociation->originNodeURI->identifier]);
                } else {
                    $lsAssociation->setOriginNodeUri($cfAssociation->destinationNodeURI->uri);
                    $lsAssociation->setOriginNodeIdentifier($cfAssociation->destinationNodeURI->identifier);
                }
            }

            if (property_exists($cfAssociation, 'destinationNodeURI') && is_object($cfAssociation->destinationNodeURI)) {
                if (array_key_exists($cfAssociation->destinationNodeURI->identifier, $items)) {
                    $lsAssociation->setDestination($items[$cfAssociation->destinationNodeURI->identifier]);
                } else {
                    $lsAssociation->setDestinationNodeUri($cfAssociation->destinationNodeURI->uri);
                    $lsAssociation->setDestinationNodeIdentifier($cfAssociation->destinationNodeURI->identifier);
                }
            }

            $em->persist($lsAssociation);
        }

        $em->flush();
    }
}
