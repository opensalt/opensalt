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
    public function parseGithubDocument($lsDocKeys, $lsItemKeys, $fileContent){
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

        $this->saveGithubDocument($lsDocKeys, $lsItemKeys, $content);
    }

    /**
     * Save an Github document into a LsDoc/LsItem hierarchy
     *
     * @param array $lsdocKeys
     * @param string $content
     */
    public function saveGithubDocument($lsDocKeys, $lsItemKeys, $content){
        $em = $this->getEntityManager();
        $lsDoc = new LsDoc();

        foreach($content as $i => $row){
            if($i < 1){
                //create the new LsDoc
                $lsDocIdentifier = Uuid::uuid5(Uuid::NAMESPACE_URL, $this->getValue($lsDocKeys['title'], $row))->toString();
                $lsDoc->setIdentifier($lsDocIdentifier);
                $lsDoc->setUri('local:'.$lsDocIdentifier);

                if(empty($this->getValue($lsDocKeys['creator'], $row))){
                    $lsDoc->setCreator('Imported from GitHub');
                }else{
                    $lsDoc->setCreator($this->getValue($lsDocKeys['creator'], $row));
                }

                $lsDoc->setTitle($this->getValue($lsDocKeys['title'], $row));

                $lsDoc->setOfficialUri($this->getValue($lsDocKeys['officialSourceURL'], $row));
                $lsDoc->setPublisher($this->getValue($lsDocKeys['publisher'], $row));
                $lsDoc->setDescription($this->getValue($lsDocKeys['description'], $row));
                $lsDoc->setVersion($this->getValue($lsDocKeys['version'], $row));
                $lsDoc->setSubject($this->getValue($lsDocKeys['subject'], $row));
                $lsDoc->setLanguage($this->getValue($lsDocKeys['language'], $row));
                $lsDoc->setNote($this->getValue($lsDocKeys['notes'], $row));

                $em->persist($lsDoc);
                $em->flush();
                continue;
            }

            $lsItem = $this->parseGithubStandard($lsDoc, $lsItemKeys, $row);
            //create a new association between the item and the doc
        }
    }

    public function parseGithubStandard(LsDoc $lsDoc, $lsItemKeys, $data){
        $lsItem = new LsItem();
        $em = $this->getEntityManager();

        $lsItem->setLsDoc($lsDoc);
        $lsItem->setFullStatement($this->getValue($lsItemKeys['fullStatement'], $data));

        $lsItemIdentifier = Uuid::uuid5(Uuid::NAMESPACE_URL, $this->getValue($lsItemKeys['fullStatement'], $data))->toString();
        $lsItem->setIdentifier($lsItemIdentifier);
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
        $em->flush();
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
