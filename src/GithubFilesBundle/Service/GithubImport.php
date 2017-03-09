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
    public function parseCSVGithubDocument($lsDocKeys, $lsItemKeys, $lsAssocKeys, $fileContent)
    {
        $this->lsAssocKeys = $lsAssocKeys;
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

        $this->saveCSVGithubDocument($lsDocKeys, $lsItemKeys, $content);
    }

    /**
     * Save an Github document into a LsDoc/LsItem hierarchy
     *
     * @param array $lsDocKeys
     * @param array $lsItemKeys
     * @param array $content
     */
    public function saveCSVGithubDocument($lsDocKeys, $lsItemKeys, $content)
    {
        $em = $this->getEntityManager();
        $lsDoc = new LsDoc();

        if (empty($this->getValue($lsDocKeys['creator'], $content[0]))) {
            $lsDoc->setCreator('Imported from GitHub');
        } else {
            $lsDoc->setCreator($this->getValue($lsDocKeys['creator'], $content[0]));
        }

        $lsDoc->setTitle($this->getValue($lsDocKeys['title'], $content[0]));
        $lsDoc->setOfficialUri($this->getValue($lsDocKeys['officialSourceURL'], $content[0]));
        $lsDoc->setPublisher($this->getValue($lsDocKeys['publisher'], $content[0]));
        $lsDoc->setDescription($this->getValue($lsDocKeys['description'], $content[0]));
        $lsDoc->setVersion($this->getValue($lsDocKeys['version'], $content[0]));
        $lsDoc->setSubject($this->getValue($lsDocKeys['subject'], $content[0]));
        $lsDoc->setLanguage($this->getValue($lsDocKeys['language'], $content[0]));
        $lsDoc->setNote($this->getValue($lsDocKeys['notes'], $content[0]));

        $lastGroupBy = "__default";
        $listGroupBy = [];
        $groupBy = "";

        $lsItems = [];
        $humanCodingValues = [];
        for ($i = 1, $iMax = count($content); $i < $iMax; ++$i) {
            $lsItem = $this->parseCSVGithubStandard($lsDoc, $lsItemKeys, $content[$i]);
            $lsItems[$i] = $lsItem;
            if ($lsItem->getHumanCodingScheme()) {
                $humanCodingValues[$lsItem->getHumanCodingScheme()] = $i;
            }
        }

        for ($i = 1, $iMax = count($content); $i < $iMax; ++$i) {
            if (strlen($lsItemKeys['groupBy']) > 0) {
                $lsItem = $lsItem[$i];
                $groupBy = $this->getValue($lsItemKeys['groupBy'], $content[$i]);
                if ($groupBy == $lastGroupBy) {
                    $listGroupBy[$groupBy]->addChild($lsItem);
                } else {
                    if (array_key_exists($groupBy, $listGroupBy)) {
                        $listGroupBy[$groupBy]->addChild($lsItem);
                    } else {
                        $listGroupBy[$groupBy] = $lsItem;
                        $lsDoc->addTopLsItem($lsItem);
                    }
                }
                $lastGroupBy = $groupBy;
            }else{
                $lsItem = $lsItems[$i];
                if ($humanCoding = $lsItem->getHumanCodingScheme()) {
                    $parent = substr($humanCoding, 0, strrpos($humanCoding, '.'));
                    if (array_key_exists($parent, $humanCodingValues)) {
                        $lsItems[$humanCodingValues[$parent]]->addChild($lsItem);
                    } else {
                        $lsDoc->addTopLsItem($lsItem);
                    }
                } else {
                    $lsDoc->addTopLsItem($lsItem);
                }
            }
        }

        $em->persist($lsDoc);
        $em->flush();
    }

    /**
     * @param LsDoc $lsDoc
     * @param array $lsItemKeys
     * @param string $data
     *
     */
    public function parseCSVGithubStandard(LsDoc $lsDoc, $lsItemKeys, $data)
    {
        $lsItem = new LsItem();
        $em = $this->getEntityManager();

        $lsItem->setLsDoc($lsDoc);
        $lsItem->setFullStatement($this->getValue($lsItemKeys['fullStatement'], $data));
        $lsItem->setHumanCodingScheme($this->getValue($lsItemKeys['humanCodingScheme'], $data));
        $lsItem->setAbbreviatedStatement($this->getValue($lsItemKeys['abbreviatedStatement'], $data));
        $lsItem->setListEnumInSource($this->getValue($lsItemKeys['listEnumeration'], $data));
        $lsItem->setConceptKeywords($this->getValue($lsItemKeys['conceptKeywords'], $data));
        $lsItem->setConceptKeywordsUri($this->getValue($lsItemKeys['conceptKeywordsUri'], $data));
        $lsItem->setLanguage($this->getValue($lsItemKeys['language'], $data));
        $lsItem->setLicenceUri($this->getValue($lsItemKeys['license'], $data));
        $lsItem->setNotes($this->getValue($lsItemKeys['notes'], $data));

        $itemsAssociated = $this->getValue($this->lsAssocKeys['isRelatedTo'], $data);

        // Adding associations if items has one.
        if (strlen($this->lsAssocKeys["isRelatedTo"]) > 0 && strlen($itemsAssociated) > 0) {
            $repository = $this->getEntityManager()->getRepository(LsItem::class);
            $humanCodingSchemes = explode(',', $itemsAssociated);
            $itemsRelated = $repository->findByHumanCodingScheme($humanCodingSchemes);
            foreach($itemsRelated as $itemRelated){
                $lsAssoc = new LsAssociation();
                $lsAssoc->setLsDoc($itemRelated->getLsDoc());
                $lsAssoc->setOrigin($itemRelated);
                $lsAssoc->setType(LsAssociation::RELATED_TO);
                $lsAssoc->setDestination($lsItem);
                $itemRelated->addAssociation($lsAssoc);
                $lsItem->addInverseAssociation($lsAssoc);
            }
        }


        $em->persist($lsItem);

        return $lsItem;
    }

    /**
     * @param string $key
     * @param string $row
     *
     * @return string
     */
    private function getValue($key, $row)
    {
        if (empty($key)) {
            return '';
        } else {
            $res = strtok($key, ',');

            if (strlen($res) !== strlen($key)) {
                return $res;
            }
        }

        return $row[$key];
    }
}
