<?php

namespace GithubFilesBundle\Service;

use CftfBundle\Entity\LsAssociation;
use CftfBundle\Entity\LsDefItemType;
use CftfBundle\Entity\LsDefSubject;
use CftfBundle\Entity\LsDoc;
use CftfBundle\Entity\LsItem;
use Doctrine\ORM\EntityManager;
use JMS\DiExtraBundle\Annotation as DI;
use Ramsey\Uuid\Uuid;

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
     * @param array $lsdocKeys
     * @param string $fileContent
     */
    public function parseCSVGithubDocument($lsDocKeys, $lsItemKeys, $fileContent){
        $csvContent = str_getcsv($fileContent, "\n");
        $headers = array();
        $content = array();
        $tempContent = array();

        foreach ($csvContent as $i => $row) {
            $row = str_getcsv($row, ",");

            if ($i == 0){
                $headers = $row;
                continue;
            }

            foreach($headers as $h => $col){
                $tempContent[$col] = $row[$h];
            }

            array_push($content, $tempContent);
            $tempContent = array();
        }

        $this->saveCSVGithubDocument($lsDocKeys, $lsItemKeys, $content);
    }

    /**
     * Save an Github document into a LsDoc/LsItem hierarchy
     *
     * @param array $lsdocKeys
     * @param string $content
     */
    public function saveCSVGithubDocument($lsDocKeys, $lsItemKeys, $content){
        $em = $this->getEntityManager();
        $lsDoc = new LsDoc();

        //create the new LsDoc
        $lsDoc->setIdentifier();
        $lsDoc->setUri('local:'.$lsDocIdentifier);

        if(empty($this->getValue($lsDocKeys['creator'], $content[0]))){
            $lsDoc->setCreator('Imported from GitHub');
        }else{
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

        $em->persist($lsDoc);

        for($i=1; $i < sizeof($content); $i++){
            $row = $content[$i];
            $lsItem = $this->parseCSVGithubStandard($lsDoc, $lsItemKeys, $row);
        }

        $em->flush();
    }

    public function parseCSVGithubStandard(LsDoc $lsDoc, $lsItemKeys, $data){
        $lsItem = new LsItem();
        $em = $this->getEntityManager();

        $lsItem->setLsDoc($lsDoc);
        $lsItem->setFullStatement($this->getValue($lsItemKeys['fullStatement'], $data));

        $lsItem->setIdentifier();
        $lsItem->setUri('local:'.$lsItemIdentifier);

        $lsItem->setHumanCodingScheme($this->getValue($lsItemKeys['humanCodingScheme'], $data));
        $lsItem->setAbbreviatedStatement($this->getValue($lsItemKeys['abbreviatedStatement'], $data));
        $lsItem->setListEnumInSource($this->getValue($lsItemKeys['listEnumeration'], $data));
        $lsItem->setConceptKeywords($this->getValue($lsItemKeys['conceptKeywords'], $data));
        $lsItem->setConceptKeywordsUri($this->getValue($lsItemKeys['conceptKeywordsUri'], $data));
        $lsItem->setLanguage($this->getValue($lsItemKeys['language'], $data));
        $lsItem->setLicenceUri($this->getValue($lsItemKeys['license'], $data));
        $lsItem->setNotes($this->getValue($lsItemKeys['notes'], $data));

        $em->persist($lsItem);

        $lsAssociation = new LsAssociation();
        $lsAssociation->setLsDoc($lsDoc);
        $lsAssociation->setOrigin($lsItem);
        $lsAssociation->setType(LsAssociation::EXACT_MATCH_OF);
        $lsAssociation->setDestinationNodeIdentifier($lsItemIdentifier);

        $em->persist($lsAssociation);

        return $lsItem;
    }

    private function getValue($key, $row){
        if (empty($key)){
            return "";
        }else{
            $res = explode(",", $key);

            if (sizeof($res) > 1){
                return $res[0];
            }
        }

        return $row[$key];
    }
}
