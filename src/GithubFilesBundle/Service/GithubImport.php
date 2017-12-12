<?php

namespace GithubFilesBundle\Service;

use CftfBundle\Entity\ImportLog;
use CftfBundle\Entity\LsDoc;
use CftfBundle\Entity\LsItem;
use CftfBundle\Entity\LsAssociation;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
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
     * @param array $missingFieldsLog
     */
    public function parseCSVGithubDocument($lsItemKeys, $fileContent, $lsDocId, $frameworkToAssociate, $missingFieldsLog)
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

        $this->saveCSVGithubDocument($lsItemKeys, $content, $lsDocId, $frameworkToAssociate, $missingFieldsLog);
    }

    /**
     * Save an Github document into a LsDoc/LsItem hierarchy
     *
     * @param array $lsDocKeys
     * @param array $lsItemKeys
     * @param array $content
     * @param array $missingFieldsLog
     */
    public function saveCSVGithubDocument($lsItemKeys, $content, $lsDocId, $frameworkToAssociate, $missingFieldsLog)
    {
        $em = $this->getEntityManager();
        $lsDoc = $em->getRepository('CftfBundle:LsDoc')->find($lsDocId);

        if (count($missingFieldsLog) > 0){
            foreach ($missingFieldsLog as $messageError) {
                $errorLog = new ImportLog();
                $errorLog->setLsDoc($lsDoc);
                $errorLog->setMessage($messageError);
                $errorLog->setMessageType('warning');

                $em->persist($errorLog);
            }
        }else{
            $successLog = new ImportLog();
            $successLog->setLsDoc($lsDoc);
            $successLog->setMessage('Items sucessful imported.');
            $successLog->setMessageType('info');

            $em->persist($successLog);
        }

        // Build the lsItems array
        $lsItems = [];
        $sequenceNumbers = [];
        $humanCodingValues = [];
        for ($i = 0, $iMax = count($content); $i < $iMax; ++$i) {
            $lineContent = $content[$i];
            if ($this->isValidItemContent($lineContent, $lsItemKeys)){
                $lsItem = $this->parseCSVGithubStandard($lsDoc, $lsItemKeys, $lineContent, $em);
            } else {
                $lsItems[$i] = null;
                continue;
            }

            if($lsItem !== null){
                $lsItems[$i] = $lsItem;
                if ($lsItem->getHumanCodingScheme()) {
                    $humanCodingValues[$lsItem->getHumanCodingScheme()] = $i;
                }

                if (array_key_exists('sequenceNumber', $lsItemKeys)) {
                    $seq = $content[$i][trim($lsItemKeys['sequenceNumber'])];
                    if (!is_numeric($seq)) {
                        $seq = null;
                    }
                    $sequenceNumbers[$i] = $seq;
                } else {
                    $sequenceNumbers[$i] = null;
                }
            }
        }

        if(count($lsItems) < 1){return;}

        // Build associations
        for ($i = 0, $iMax = count($content); $i < $iMax; ++$i) {
            $lsItem = $lsItems[$i];
            if (null === $lsItem || !array_key_exists('isChildOf', $lsItemKeys)) { continue; }

            // Check if the lsItems UUID exists in the ls_association table and
            // if it is do not create a new association. This allows content
            // updates but doesn't double up the associations.
            $thisItemsUUID = $content[$i]['Identifier'];
            $associationExists = $em->getRepository('CftfBundle:LsAssociation')->findOneBy(['originNodeIdentifier' => $thisItemsUUID]);
            $logDetails = date('Y/m/d h:i:s A ')."The lsItem with the Human coding scheme of {$content[$i]['Human Coding Scheme']} and UUID of {$thisItemsUUID} has been added.";

            if (!$associationExists) {
                // Log new items added.
                $errorLog = new ImportLog();
                $errorLog->setLsDoc($lsDoc);
                $errorLog->setMessage($logDetails);
                $errorLog->setMessageType('warning');

                $em->persist($errorLog);
                // check if the item returns a humancodingscheme
                if ($humanCoding = $lsItem->getHumanCodingScheme()) {
                    $parent = $content[$i][$lsItemKeys['isChildOf']];
                    if (empty($parent)) {
                        $parent = substr($humanCoding, 0, strrpos($humanCoding, '.'));
                    }

                    if (array_key_exists($parent, $humanCodingValues)) {
                        $lsItems[$humanCodingValues[$parent]]->addChild($lsItem, null, $sequenceNumbers[$i]);
                    } else {
                        $lsDoc->addTopLsItem($lsItem, null, $sequenceNumbers[$i]);
                    }
                }
                $this->saveAssociations($i, $content, $lsItemKeys, $lsItem, $lsDoc, $frameworkToAssociate);
            }
        }

        $em->flush();
    }

    /**
     * @param int       $position
     * @param array     $content
     * @param array     $lsItemKeys
     * @param LsItem    $lsItem
     * @param LsDoc     $lsDoc
     * @param string    $frameworkToAssociate
     */
    public function saveAssociations($position, $content, $lsItemKeys, LsItem $lsItem, LsDoc $lsDoc, $frameworkToAssociate)
    {
        $fieldsAndTypes = LsAssociation::allTypesForImportFromCSV();
        // We don't use is_child_of because that it alaready used to create parents relations before. :)
        // checking each association field
        foreach ($fieldsAndTypes as $fieldName => $assocType){
            if (array_key_exists($fieldName, $lsItemKeys) && $cfAssociations = $content[$position][trim($lsItemKeys[$fieldName])]) {
                foreach (explode(',', $cfAssociations) as $cfAssociation) {
                    $this->addItemRelated($lsDoc, $lsItem, $cfAssociation, $frameworkToAssociate, $assocType);
                }
            }
        }
    }

    /**
     * @param LsDoc   $lsDoc
     * @param LsItem  $lsItem
     * @param string  $cfAssociation
     * @param string  $frameworkToAssociate
     * @param string  $assocType
     */
    public function addItemRelated(LsDoc $lsDoc, LsItem $lsItem, $cfAssociation, $frameworkToAssociate, $assocType)
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
                    $this->saveAssociation($lsDoc, $lsItem, $itemAssociated, $assocType, null);
                }
            } else {
                $this->saveAssociation($lsDoc, $lsItem, $cfAssociation, $assocType, null);
            }
        }
    }

    /**
     * @param LsDoc $lsDoc
     * @param LsItem $lsItem
     * @param string|LsItem $elementAssociated
     * @param string $assocType
     */
    public function saveAssociation(LsDoc $lsDoc, LsItem $lsItem, $elementAssociated, $assocType)
    {
        $association = new LsAssociation();
        $association->setType($assocType);
        $association->setLsDoc($lsDoc);
        $association->setOrigin($lsItem);
        if (is_string($elementAssociated)) {
            if (\Ramsey\Uuid\Uuid::isValid($elementAssociated)) {
                $association->setDestinationNodeIdentifier($elementAssociated);
            } elseif (!filter_var($elementAssociated, FILTER_VALIDATE_URL) === false) {
                $association->setDestinationNodeUri($elementAssociated);
                $association->setDestinationNodeIdentifier(Uuid::uuid5(Uuid::NAMESPACE_URL, $elementAssociated));
            } else {
                $encodedHumanCodingScheme = $this->encodeHumanCodingScheme($elementAssociated);
                $association->setDestinationNodeUri($encodedHumanCodingScheme);
                $association->setDestinationNodeIdentifier(Uuid::uuid5(Uuid::NAMESPACE_URL, $encodedHumanCodingScheme));
            }
        } else {
            $association->setDestination($elementAssociated);
        }
        $this->getEntityManager()->persist($association);
    }

    /**
     * @param string $humanCodingScheme
     *
     * @return string
     */
    protected function encodeHumanCodingScheme($humanCodingScheme): string
    {
        $prefix = 'data:text/x-ref-unresolved;base64,';
        return $prefix.base64_encode($humanCodingScheme);
    }

    /**
     * @param LsDoc $lsDoc
     * @param array $lsItemKeys
     * @param array $data
     */
    public function parseCSVGithubStandard(LsDoc $lsDoc, $lsItemKeys, $data, $em)
    {
        // Query the db for matching UUIDs.
        $resultOfSearch = $em->getRepository('CftfBundle:LsItem')->findOneBy(array('identifier' => $data[$lsItemKeys['identifier']]));

        // If not in the DB create a new lsItem.
        if ($resultOfSearch === null) {

            $lsItem = new LsItem();
            $em = $this->getEntityManager();
            $itemAttributes = ['humanCodingScheme', 'abbreviatedStatement', 'conceptKeywords', 'language', 'license', 'notes'];

            $lsItem = $this->assignValuesToItem($lsItem, $lsDoc, $lsItemKeys, $data, $itemAttributes);

            $em->persist($lsItem);

            return $lsItem;
        } else { // Update if in db and changed.
            $fullStatement = $resultOfSearch->getFullStatement();
            if ($data[$lsItemKeys['fullStatement']] != $fullStatement) {
                $itemAttributes = ['humanCodingScheme', 'abbreviatedStatement', 'conceptKeywords', 'language', 'license', 'notes'];

                $lsItem = $this->assignValuesToItem($resultOfSearch, $lsDoc, $lsItemKeys, $data, $itemAttributes);

                $em->persist($lsItem);

                // Log if we make an update.
                $logDetails = date('Y/m/d h:i:s A ')."The lsItem with the Human coding scheme of {$data[$lsItemKeys['humanCodingScheme']]} has been updated.";
                $errorLog = new ImportLog();
                $errorLog->setLsDoc($lsDoc);
                $errorLog->setMessage($logDetails);
                $errorLog->setMessageType('warning');

                $em->persist($errorLog);

                return $lsItem;
            } else {
                return $resultOfSearch;
            }
        }
    }

    /**
     * It returns true|false is a line from content has the requried columns
     *
     * @param array   $lineContent
     * @param array   $lsItemKeys
     *
     * @return        bool
     */
    private function isValidItemContent(array $lineContent, array $lsItemKeys): bool
    {
        return ($lineContent[$lsItemKeys['fullStatement']] ?? '') !== '';
    }

    /**
     * assign values relatd with the key to a Item
     *
     * @param LsItem  $lsItem
     * @param array   $lsItemKeys
     * @param array   $lineContent
     * @param array   $keys
     * @param LsDoc   $lsDoc
     *
     * @return        LsItem
     */
    private function assignValuesToItem(LsItem $lsItem, LsDoc $lsDoc, array $lsItemKeys, array $lineContent, array $keys): LsItem
    {
        $lsItem->setLsDoc($lsDoc);
        $lsItem->setFullStatement($lineContent[$lsItemKeys['fullStatement']]);
        if (array_key_exists($lsItemKeys['identifier'], $lineContent) && \Ramsey\Uuid\Uuid::isValid($lineContent[$lsItemKeys['identifier']])) {
            $lsItem->setIdentifier($lineContent[$lsItemKeys['identifier']]);
            $lsItem->setUri('local:'.$lineContent[$lsItemKeys['identifier']]);
        }
        foreach ($keys as $key) {
            if (array_key_exists($key, $lsItemKeys) && array_key_exists($lsItemKeys[$key], $lineContent)) $lsItem->{'license' === $key ? 'setLicenceUri' : 'set'.ucfirst($key)}($lineContent[$lsItemKeys[$key]]);
        }

        return $lsItem;
    }
}
