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

        $lsDoc->setIdentifier($this->ifExists($fileContent->CFDocument->identifier));
        $lsDoc->setUri($this->ifExists($fileContent->CFDocument->uri));
        $lsDoc->setCreator($this->ifExists($fileContent->CFDocument->creator));
        $lsDoc->setPublisher($this->ifExists($fileContent->CFDocument->publisher));
        $lsDoc->setTitle($this->ifExists($fileContent->CFDocument->title));
        $lsDoc->setNote($this->ifExists($fileContent->CFDocument->notes));
        $lsDoc->setOfficialUri($this->ifExists($fileContent->CFDocument->officialSourceURL));
        $lsDoc->setVersion($this->ifExists($fileContent->CFDocument->version));
        $lsDoc->setDescription($this->ifExists($fileContent->CFDocument->description));
        $lsDoc->setLanguage($this->ifExists($fileContent->CFDocument->language));

        $em->persist($lsDoc);

        $cfItems = $fileContent->CFItems;
        foreach ($cfItems as $cfItem) {
            $lsItem = new LsItem();

            $lsItem->setLsDoc($lsDoc);
            $lsItem->setIdentifier($this->ifExists($cfItem->identifier));
            $lsItem->setUri($this->ifExists($cfItem->uri));
            $lsItem->setFullStatement($this->ifExists($cfItem->fullStatement));
            $lsItem->setListEnumInSource($this->ifExists($cfItem->listEnumeration));
            $lsItem->setHumanCodingScheme($this->ifExists($cfItem->humanCodingScheme));
            $lsItem->setAbbreviatedStatement($this->ifExists($cfItem->abbreviatedStatement));
            $lsItem->setNotes($this->ifExists($cfItem->notes));
            $lsItem->setEducationalAlignment($this->ifExists($cfItem->educationAlignment));
            $lsItem->setLanguage($this->ifExists($cfItem->language));

            $em->persist($lsItem);
        }

        $cfAssociations = $fileContent->CFAssociations;
        foreach ($cfAssociations as $cfAssociation) {
            $lsAssociation = new LsAssociation();

            $lsAssociation->setLsDoc($lsDoc);
            $lsAssociation->setIdentifier($this->ifExists($cfAssociation->identifier));
            $lsAssociation->setUri($this->ifExists($cfAssociation->uri));
            $lsAssociation->setType($this->ifExists($cfAssociation->associationType));
            $lsAssociation->setGroupName($this->ifExists($cfAssociation->groupName));

            if ($this->ifExists($cfAssociation-originNodeURI)) {
                if (is_object($cfAssociation->originNodeURI)) {
                    $lsAssociation->setOriginNodeUri($cfAssociation->destinationNodeURI->uri);
                    $lsAssociation->setOriginNodeIdentifier($cfAssociation->destinationNodeURI->identifier);
                }
            }

            if ($this->ifExists($cfAssociation->destinationNodeURI)){
                if (is_object($cfAssociation->destinationNodeURI)) {
                    $lsAssociation->setDestinationNodeUri($cfAssociation->destinationNodeURI->uri);
                    $lsAssociation->setDestinationNodeIdentifier($cfAssociation->destinationNodeURI->identifier);
                }
            }

            $em->persist($lsAssociation);
        }

        $em->flush();
    }

    public function ifExists($data)
    {
        return isset($data) ? $data : '';
    }
}
