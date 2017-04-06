<?php

namespace GithubFilesBundle\Service;

use CftfBundle\Entity\LsDoc;
use CftfBundle\Entity\LsItem;
use CftfBundle\Entity\LsAssociation;
use Doctrine\ORM\EntityManager;
use JMS\DiExtraBundle\Annotation as DI;

/**
 * Class GithubImport.
 *
 * @DI\Service("cftf_import.github")
 */
class GithubImport
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @param EntityManager $em
     *
     * @DI\InjectParams({
     *     "em" = @DI\Inject("doctrine.orm.entity_manager"),
     * })
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        return $this->em;
    }

    /**
     * Parse an Github document into a LsDoc/LsItem hierarchy
     *
     * @param array $lsDocKeys
     * @param array $lsItemKeys
     * @param string $fileContent
     */
    public function parseCSVGithubDocument($lsItemKeys, $fileContent, $lsDocId)
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

        $this->saveCSVGithubDocument($lsItemKeys, $content, $lsDocId);
    }

    /**
     * Save an Github document into a LsDoc/LsItem hierarchy
     *
     * @param array $lsDocKeys
     * @param array $lsItemKeys
     * @param array $content
     */
    public function saveCSVGithubDocument($lsItemKeys, $content, $lsDocId)
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
                foreach(explode(',', $cfAssociations) as $cfAssociation){
                    $this->addItemRelated($lsDoc, $lsItem, $cfAssociation);
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
    public function addItemRelated(LsDoc $lsDoc, $lsItem, $cfAssociation){
        $em = $this->getEntityManager();
        if (strlen(trim($cfAssociation)) > 0) {
            $itemsAssociated = $em->getRepository('CftfBundle:LsItem')
                ->findAllByIdentifierOrHumanCodingScheme($cfAssociation);

            if (count($itemsAssociated) > 0)
            {
                foreach ($itemsAssociated as $itemAssociated)
                {
                    $this->saveAssociation($lsDoc, $lsItem, $itemAssociated, $em);
                }
            }else{
                $this->saveAssociation($lsDoc, $lsItem, $cfAssociation, $em);
            }
        }
    }

    /*
     * @param LsDoc $lsDoc
     * @param LsItem $lsItem
     * @param string|LsItem $itemAssociated
     * @param EntityManager $em
     */
    public function saveAssociation($lsDoc, $lsItem, $elementAssociated, $em)
    {
        $association = new LsAssociation();
        $association->setType(LsAssociation::RELATED_TO);
        $association->setLsDoc($lsDoc);
        $association->setOrigin($lsItem);
        if (is_string($elementAssociated))
        {
            $association->setDestinationNodeIdentifier($elementAssociated);
        } else {
            $association->setDestination($elementAssociated);
        }
        $em->persist($association);
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
