<?php

namespace App\Service;

use App\Entity\Framework\LsDefItemType;
use App\Entity\Framework\LsDoc;
use App\Entity\Framework\LsItem;
use App\Entity\Framework\LsAssociation;
use App\Util\EducationLevelSet;
use Doctrine\ORM\EntityManagerInterface;

class CaseImport
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function getEntityManager(): EntityManagerInterface
    {
        return $this->entityManager;
    }

    public function importCaseFile(\stdClass $fileContent): LsDoc
    {
        set_time_limit(180); // increase time limit for large files

        $em = $this->getEntityManager();
        $lsDoc = new LsDoc($fileContent->CFDocument->identifier);

        if (property_exists($fileContent->CFDocument, 'uri')) {
            $lsDoc->setUri($fileContent->CFDocument->uri);
        }
        if (property_exists($fileContent->CFDocument, 'creator')) {
            $lsDoc->setCreator($fileContent->CFDocument->creator);
        }
        if (property_exists($fileContent->CFDocument, 'publisher')) {
            $lsDoc->setPublisher($fileContent->CFDocument->publisher);
        }
        if (property_exists($fileContent->CFDocument, 'title')) {
            $lsDoc->setTitle($fileContent->CFDocument->title);
        }
        if (property_exists($fileContent->CFDocument, 'notes')) {
            $lsDoc->setNote($fileContent->CFDocument->notes);
        }
        if (property_exists($fileContent->CFDocument, 'officialSourceURL')) {
            $lsDoc->setOfficialUri($fileContent->CFDocument->officialSourceURL);
        }
        if (property_exists($fileContent->CFDocument, 'version')) {
            $lsDoc->setVersion($fileContent->CFDocument->version);
        }
        if (property_exists($fileContent->CFDocument, 'description')) {
            $lsDoc->setDescription($fileContent->CFDocument->description);
        }
        if (property_exists($fileContent->CFDocument, 'language')) {
            $lsDoc->setLanguage($fileContent->CFDocument->language);
        }
        if (property_exists($fileContent->CFDocument, 'adoptionStatus')) {
            $lsDoc->setAdoptionStatus($fileContent->CFDocument->adoptionStatus);
        }
        if (property_exists($fileContent->CFDocument, 'statusStartDate')) {
            $lsDoc->setStatusStart(new \DateTime($fileContent->CFDocument->statusStartDate));
        }
        if (property_exists($fileContent->CFDocument, 'statusEndDate')) {
            $lsDoc->setStatusEnd(new \DateTime($fileContent->CFDocument->statusEndDate));
        }

        $em->persist($lsDoc);

        $cfItemTypes = [];

        $cfItems = $fileContent->CFItems;
        $items = [];
        $items[$lsDoc->getIdentifier()] = $lsDoc;
        foreach ($cfItems as $cfItem) {
            $lsItem = new LsItem($cfItem->identifier);

            $lsItem->setLsDoc($lsDoc);
            if (property_exists($cfItem, 'uri')) {
                $lsItem->setUri($cfItem->uri);
            }
            if (property_exists($cfItem, 'fullStatement')) {
                $lsItem->setFullStatement($cfItem->fullStatement);
            }
            if (property_exists($cfItem, 'listEnumeration')) {
                $lsItem->setListEnumInSource($cfItem->listEnumeration);
            }
            if (property_exists($cfItem, 'humanCodingScheme')) {
                $lsItem->setHumanCodingScheme($cfItem->humanCodingScheme);
            }
            if (property_exists($cfItem, 'abbreviatedStatement')) {
                $lsItem->setAbbreviatedStatement($cfItem->abbreviatedStatement);
            }
            if (property_exists($cfItem, 'notes')) {
                $lsItem->setNotes($cfItem->notes);
            }
            if (property_exists($cfItem, 'educationLevel')) {
                $importedGrades = $cfItem->educationLevel;
                if (is_string($importedGrades)) {
                    $importedGrades = str_replace(' ', '', $importedGrades);
                    $importedGrades = explode(',', $importedGrades);
                } elseif (!is_array($cfItem->educationLevel)) {
                    // Skip invalid data
                    continue;
                }

                $grades = EducationLevelSet::fromArray($importedGrades);
                $lsItem->setEducationalAlignment($grades->toString());
            }
            if (property_exists($cfItem, 'language')) {
                $lsItem->setLanguage($cfItem->language);
            }
            if (property_exists($cfItem, 'CFItemType')) {
                if (empty($cfItemTypes[$cfItem->CFItemType])) {
                    $itemType = new LsDefItemType();
                    $itemType->setTitle($cfItem->CFItemType);
                    $itemType->setDescription($cfItem->CFItemType);
                    $itemType->setCode($cfItem->CFItemType);
                    $itemType->setHierarchyCode(1);
                    $em->persist($itemType);

                    $cfItemTypes[$cfItem->CFItemType] = $itemType;
                }

                $lsItem->setItemType($cfItemTypes[$cfItem->CFItemType]);
            }

            $em->persist($lsItem);
            $items[$cfItem->identifier] = $lsItem;
        }

        $cfAssociations = $fileContent->CFAssociations;
        foreach ($cfAssociations as $cfAssociation) {
            $lsAssociation = new LsAssociation($cfAssociation->identifier);

            $lsAssociation->setLsDoc($lsDoc);
            if (property_exists($cfAssociation, 'uri')) {
                $lsAssociation->setUri($cfAssociation->uri);
            }
            if (property_exists($cfAssociation, 'associationType')) {
                $associationType = ucfirst(preg_replace('/([A-Z])/', ' $1', $cfAssociation->associationType));
                if (in_array($associationType, LsAssociation::allTypes(), true)) {
                    $lsAssociation->setType($associationType);
                }
            }

            if (property_exists($cfAssociation, 'sequenceNumber')) {
                $lsAssociation->setSequenceNumber($cfAssociation->sequenceNumber);
            }

            if (property_exists($cfAssociation, 'groupName')) {
                $lsAssociation->setGroupName($cfAssociation->groupName);
            }

            if (property_exists($cfAssociation, 'originNodeURI') && is_object($cfAssociation->originNodeURI)) {
                if (array_key_exists($cfAssociation->originNodeURI->identifier, $items)) {
                    $lsAssociation->setOrigin($items[$cfAssociation->originNodeURI->identifier]);
                } else {
                    $lsAssociation->setOriginNodeUri($cfAssociation->originNodeURI->uri);
                    $lsAssociation->setOriginNodeIdentifier($cfAssociation->originNodeURI->identifier);
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

        return $lsDoc;
    }
}
