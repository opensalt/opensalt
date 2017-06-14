<?php

namespace CftfBundle\Service;

use CftfBundle\Entity\LsDoc;
use CftfBundle\Entity\LsItem;
use CftfBundle\Entity\LsAssociation;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use GithubFilesBundle\Service\GithubImport;
use JMS\DiExtraBundle\Annotation as DI;

/**
 * Class FrameworkUpdater.
 *
 * @DI\Service("framework_updater.local")
 */
class FrameworkUpdater
{
    /**
     * @var ManagerRegistry
     */
    private $managerRegistry;

    /**
     * @param ManagerRegistry $managerRegistry
     * @param GithubImport $githubImport
     *
     * @DI\InjectParams({
     *     "managerRegistry" = @DI\Inject("doctrine"),
     *     "githubImport" = @DI\Inject("cftf_import.github"),
     * })
     */
    public function __construct(ManagerRegistry $managerRegistry, GithubImport $githubImport)
    {
        $this->managerRegistry = $managerRegistry;
        $this->githubImport = $githubImport;
    }

    /**
     * @return ObjectManager
     */
    protected function getEntityManager()
    {
        return $this->managerRegistry->getManagerForClass(LsDoc::class);
    }

    /**
     * Update framework from a CSV
     *
     * @param LsDoc  $lsDoc
     * @param string $fileContent
     * @param string $frameworkToAssociate
     */
    public function update($lsDoc, $fileContent, $frameworkToAssociate, $cfItemKeys)
    {
        $em = $this->getEntityManager();
        $this->cfItemKeys = $cfItemKeys;
        $contentTransformed = $this->transformContent($fileContent);
        $cfItems = [];

        for ($i = 0, $iMax = count($contentTransformed); $i < $iMax; ++$i)
        {
            $cfItem = $em
                ->getRepository('CftfBundle:LsItem')
                    ->findOneByIdentifier($contentTransformed[$i]['Identifier']);

            if (!$cfItem){ continue; }
            $cfItem->setLsDoc($lsDoc);
            $cfItem->setFullStatement($contentTransformed[$i][$cfItemKeys['fullStatement']]);
            $cfItem->setHumanCodingScheme($contentTransformed[$i][$cfItemKeys['humanCodingScheme']]);
            $cfItem->setAbbreviatedStatement($contentTransformed[$i][$cfItemKeys['abbreviatedStatement']]);
            $cfItem->setConceptKeywords($contentTransformed[$i][$cfItemKeys['conceptKeywords']]);
            $cfItem->setLanguage($contentTransformed[$i][$cfItemKeys['language']]);
            $cfItem->setLicenceUri($contentTransformed[$i][$cfItemKeys['license']]);
            $cfItem->setNotes($contentTransformed[$i][$cfItemKeys['notes']]);
            $this->updateAssociations($cfItem, $contentTransformed[$i], $em);
            $cfItems[] = $cfItem;
        }
        $em->flush();
    }

    /**
     * Update framework from a CSV in a new derivative framework
     *
     * @param LsDoc  $lsDoc
     * @param string $fileContent
     * @param string $frameworkToAssociate
     */
    public function derive($lsDoc, $fileContent, $frameworkToAssociate)
    {
        $em = $this->getEntityManager();
        $contentTransformed = $this->transformContent($fileContent);
        $cfItems = [];

        $newCfDocDerivated = $em->getRepository('CftfBundle:LsDoc')->makeDerivative($lsDoc);

        $em->persist($newCfDocDerivated);

        foreach($lsDoc->getTopLsItems() as $oldTopItem){
            $newItem = $oldTopItem->duplicateToLsDoc($newCfDocDerivated, null, true);

            $newAssoc = $newCfDocDerivated->createAssociation();
            $newAssoc->setOrigin($newItem);
            $newAssoc->setType(LsAssociation::EXACT_MATCH_OF);
            $newAssoc->setDestination($oldTopItem);
            $newItem->addAssociation($newAssoc);
            $em->persist($newItem);

            $newCfDocDerivated->addTopLsItem($newItem);
        }

        $em->flush();

        return $newCfDocDerivated;
    }

    /**
     * Add associations not existances on this CfItem
     *
     * @param LsItem $lsItem
     * @param array $rowContent
     */
    private function updateAssociations(LsItem $lsItem, array $rowContent, $em)
    {
        $associationTypes = LsAssociation::allTypesForImportFromCSV();
        $cfItemKeys = $this->cfItemKeys;
        $assocNotMatched = [];

        foreach ($associationTypes as $assoTypeKey => $assoTypeValue) {
            foreach (explode(',', $rowContent[$cfItemKeys[$assoTypeKey]]) as $associationSeparatedByComma) {

                if ($associationSeparatedByComma === '') { continue; }

                if (!array_key_exists($associationSeparatedByComma, $assocNotMatched)){
                    $assocNotMatched[$associationSeparatedByComma] = ['matched' => true, 'type' => $assoTypeValue];
                }

                foreach ($lsItem->getAssociations() as $association){

                    $destination = $association->getDestination();

                    if (LsAssociation::INVERSE_EXACT_MATCH_OF === $association->getType()
                        || LsAssociation::CHILD_OF === $association->getType()) {
                        continue;
                    }

                    if (is_string($destination)){
                        if ($this->validatePresenceOnAssociation($destination, $associationSeparatedByComma)) {
                            $assocNotMatched[$associationSeparatedByComma]['matched'] = false;
                        } elseif ('data:text/x-ref-unresolved;base64,' === substr($destination, 0, 34)) {
                            if ($this->validatePresenceOnAssociation($association->getHumanCodingSchemeFromDestinationNodeUri(),
                                $associationSeparatedByComma)){
                                $assocNotMatched[$associationSeparatedByComma]['matched'] = false;
                            }
                        }
                    } elseif ($destination instanceof LsItem) {
                        if ($this->validatePresenceOnAssociation($destination->getHumanCodingScheme(),
                            $associationSeparatedByComma)){
                            $assocNotMatched[$associationSeparatedByComma] = ['matched' => false, 'type' => $assoTypeValue];
                        }
                    }

                }
            }
        }
        if (count($assocNotMatched) > 0){
            foreach($assocNotMatched as $assocForMatch => $assocForMatchValues){
                if (!$assocForMatchValues['matched'] === false) {
                    $this->githubImport->addItemRelated($lsItem->getLsDoc(), $lsItem, $assocForMatch, 'all', $assocForMatchValues['type']);
                }
            }
        }
    }

    /* getHumanCodingSchemeFromDestinationNodeUri */

    /**
     * Return true or false if item has a association
     *
     * @param string $associationValue
     * @param string $associationOnContent
     */
    private function validatePresenceOnAssociation(string $associationValue, string $associationOnContent)
    {
        return $associationOnContent === $associationValue;
    }

    /**
     * Transform string content in arrays per line.
     *
     * @param string $fileContent
     *
     * @return array
     */
    protected function transformContent($fileContent)
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

        return $content;
    }

}
