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
    public function parseGithubDocument($lsdocKeys, $fileContent){
        $csvContent = str_getcsv($fileContent);
        var_dump($csvContent);
        /* while(jeee */
        /* while (($buffer = fgetcsv($csvContent, 1)) !== false) { */
        /*     echo $buffer; */
        /* } */
    }
}
