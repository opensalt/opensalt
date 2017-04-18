<?php

namespace GithubFilesBundle\Service;

use CftfBundle\Entity\LsDoc;
use CftfBundle\Entity\LsItem;
use CftfBundle\Entity\LsAssociation;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use JMS\DiExtraBundle\Annotation as DI;

/**
 * Class GithubImport.
 *
 * @DI\Service("cftf_import.github")
 */
class GithubImport
{
    /**
     * @var ManagerRegistry
     */
    private $managerRegistry;

    /**
     * @param ManagerRegistry $managerRegistry
     *
     * @DI\InjectParams({
     *     "managerRegistry" = @DI\Inject("doctrine"),
     * })
     */
    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * @return ObjectManager
     */
    protected function getEntityManager()
    {
        return $this->managerRegistry->getManagerForClass(LsDoc::class);
    }

    /**
     * Parse an Github document into a LsDoc/LsItem hierarchy
     *
     * @param array $lsDocKeys
     * @param array $lsItemKeys
     * @param string $fileContent
     * @param string $frameworkToAssociate
     */
    public function parseCSVGithubDocument($lsItemKeys, $fileContent, $lsDocId, $frameworkToAssociate)
    {
        $csvContent = str_getcsv($fileContent, "\n");
        $headers = [];
        $content = [];

        foreach ($csvContent as $i => $row) {
            $tempContent = [];
            $row = str_getcsv($row, ',');

            if ($i === 0) {
                $headers = $row;
                continue;
            }

            foreach ($headers as $h => $col) {
                if ($h < count($row)) {
                    $tempContent[$col] = $row[$h];
                }
            }

            $content[] = $tempContent;
        }

        $this->saveCSVGithubDocument($lsItemKeys, $content, $lsDocId, $frameworkToAssociate);
    }

    /**
     * Save an Github document into a LsDoc/LsItem hierarchy
     *
     * @param array $lsDocKeys
     * @param array $lsItemKeys
     * @param array $content
     */
    public function saveCSVGithubDocument($lsItemKeys, $content, $lsDocId, $frameworkToAssociate)
    {
        $em = $this->getEntityManager();
        $lsDoc = $em->getRepository('CftfBundle:LsDoc')->find($lsDocId);

        $lsItems = [];
        $humanCodingValues = [];
        for ($i = 0, $iMax = count($content); $i < $iMax; ++$i) {
            $lsItem = $this->parseCSVGithubStandard($lsDoc, $lsItemKeys, $content[$i]);
            $lsItems[$i] = $lsItem;
            if ($lsItem->getHumanCodingScheme()) {
                $humanCodingValues[$lsItem->getHumanCodingScheme()] = $i;
            }
        }

        for ($i = 0, $iMax = count($content); $i < $iMax; ++$i) {
            $lsItem = $lsItems[$i];

            if ($humanCoding = $lsItem->getHumanCodingScheme()) {
                $parent = $content[$i][$lsItemKeys['isChildOf']];
                if (empty($parent)) {
                    $parent = substr($humanCoding, 0, strrpos($humanCoding, '.'));
                }

                if (array_key_exists($parent, $humanCodingValues)) {
                    $lsItems[$humanCodingValues[$parent]]->addChild($lsItem);
                } else {
                    $lsDoc->addTopLsItem($lsItem);
                }
            }
            if ($cfAssociations = $content[$i][$lsItemKeys['cfAssociationGroupIdentifier']]) {
                foreach (explode(',', $cfAssociations) as $cfAssociation) {
                    $this->addItemRelated($lsDoc, $lsItem, $cfAssociation, $frameworkToAssociate);
                }
            }
        }

        $em->flush();
    }

    /**
     * @param LsDoc   $lsDoc
     * @param LsItem  $lsItem
     * @param string  $cfAssociation
     */
    public function addItemRelated(LsDoc $lsDoc, LsItem $lsItem, $cfAssociation, $frameworkToAssociate)
    {
        $em = $this->getEntityManager();
        if (strlen(trim($cfAssociation)) > 0) {
            if ($frameworkToAssociate === 'all') {
                $itemsAssociated = $em->getRepository('CftfBundle:LsItem')
                    ->findAllByIdentifierOrHumanCodingSchemeByValue($cfAssociation);
            } else {
                $itemsAssociated = $em->getRepository('CftfBundle:LsItem')
                    ->findByAllIdentifierOrHumanCodingSchemeByLsDoc($frameworkToAssociate, $cfAssociation);
            }

            if (count($itemsAssociated) > 0) {
                foreach ($itemsAssociated as $itemAssociated) {
                    $this->saveAssociation($lsDoc, $lsItem, $itemAssociated);
                }
            } else {
                $this->saveAssociation($lsDoc, $lsItem, $cfAssociation);
            }
        }
    }

    /**
     * @param LsDoc $lsDoc
     * @param LsItem $lsItem
     * @param string|LsItem $itemAssociated
     */
    public function saveAssociation(LsDoc $lsDoc, LsItem $lsItem, $elementAssociated)
    {
        $association = new LsAssociation();
        $association->setType(LsAssociation::RELATED_TO);
        $association->setLsDoc($lsDoc);
        $association->setOrigin($lsItem);
        if (is_string($elementAssociated)) {
            $association->setDestinationNodeIdentifier($elementAssociated);
        } else {
            $association->setDestination($elementAssociated);
        }
        $this->getEntityManager()->persist($association);
    }

    /**
     * @param LsDoc $lsDoc
     * @param array $lsItemKeys
     * @param array $data
     */
    public function parseCSVGithubStandard(LsDoc $lsDoc, $lsItemKeys, $data)
    {
        $lsItem = new LsItem();
        $em = $this->getEntityManager();

        $lsItem->setLsDoc($lsDoc);
        $lsItem->setIdentifier($data[$lsItemKeys['identifier']]);
        $lsItem->setFullStatement($data[$lsItemKeys['fullStatement']]);
        $lsItem->setHumanCodingScheme($data[$lsItemKeys['humanCodingScheme']]);
        $lsItem->setAbbreviatedStatement($data[$lsItemKeys['abbreviatedStatement']]);
        $lsItem->setConceptKeywords($data[$lsItemKeys['conceptKeywords']]);
        $lsItem->setLanguage($data[$lsItemKeys['language']]);
        $lsItem->setLicenceUri($data[$lsItemKeys['license']]);
        $lsItem->setNotes($data[$lsItemKeys['notes']]);

        $em->persist($lsItem);

        return $lsItem;
    }
}
