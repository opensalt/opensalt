<?php

namespace App\Service;

use App\Entity\Framework\LsAssociation;
use App\Entity\Framework\LsDoc;
use App\Entity\Framework\LsItem;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;

/**
 * @deprecated
 */
class FrameworkUpdater
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    protected function getEntityManager(): EntityManagerInterface
    {
        return $this->entityManager;
    }

    /**
     * Update framework from a CSV.
     */
    public function update(LsDoc $lsDoc, string $fileContent, string $frameworkToAssociate, array $cfItemKeys): void
    {
        $em = $this->getEntityManager();
        $contentTransformed = $this->transformContent($fileContent);

        foreach (array_keys($contentTransformed) as $i) {
            $cfItem = $em->getRepository(LsItem::class)
                ->findOneByIdentifier($contentTransformed[$i]['Identifier']);

            if (!$cfItem) {
                continue;
            }
            $cfItem->setLsDoc($lsDoc);
            $cfItem->setFullStatement($contentTransformed[$i][$cfItemKeys['fullStatement']]);
            $cfItem->setHumanCodingScheme($contentTransformed[$i][$cfItemKeys['humanCodingScheme']]);
            $cfItem->setAbbreviatedStatement($contentTransformed[$i][$cfItemKeys['abbreviatedStatement']]);
            $cfItem->setConceptKeywordsString($contentTransformed[$i][$cfItemKeys['conceptKeywords']]);
            $cfItem->setLanguage($contentTransformed[$i][$cfItemKeys['language']]);
            //$cfItem->setLicenceUri($contentTransformed[$i][$cfItemKeys['license']]);
            $cfItem->setNotes($contentTransformed[$i][$cfItemKeys['notes']]);

            $this->updateAssociations($cfItem, $contentTransformed[$i], $cfItemKeys);
        }

        $em->flush();
    }

    /**
     * Add associations not existances on this CfItem.
     */
    private function updateAssociations(LsItem $lsItem, array $rowContent, array $cfItemKeys): void
    {
        $associationTypes = LsAssociation::allTypesForImportFromCSV();
        $assocNotMatched = [];

        foreach ($associationTypes as $assocTypeKey => $assocTypeValue) {
            foreach (explode(',', $rowContent[$cfItemKeys[$assocTypeKey]]) as $associationSeparatedByComma) {
                if ('' === $associationSeparatedByComma) {
                    continue;
                }

                if (!array_key_exists($associationSeparatedByComma, $assocNotMatched)) {
                    $assocNotMatched[$associationSeparatedByComma] = ['matched' => true, 'type' => $assocTypeValue];
                }

                foreach ($lsItem->getAssociations() as $association) {
                    $destination = $association->getDestination();

                    if (in_array($association->getType(), [LsAssociation::INVERSE_EXACT_MATCH_OF, LsAssociation::CHILD_OF], true)) {
                        continue;
                    }

                    if (is_string($destination)) {
                        if ($this->validatePresenceOnAssociation($destination, $associationSeparatedByComma)) {
                            $assocNotMatched[$associationSeparatedByComma]['matched'] = false;
                        } elseif ('data:text/x-ref-unresolved;base64,' === substr($destination, 0, 34)) {
                            if ($this->validatePresenceOnAssociation($association->getHumanCodingSchemeFromDestinationNodeUri(), $associationSeparatedByComma)) {
                                $assocNotMatched[$associationSeparatedByComma]['matched'] = false;
                            }
                        }
                    } elseif ($destination instanceof LsItem) {
                        if ($this->validatePresenceOnAssociation($destination->getHumanCodingScheme(), $associationSeparatedByComma)) {
                            $assocNotMatched[$associationSeparatedByComma] = ['matched' => false, 'type' => $assocTypeValue];
                        }
                    }
                }
            }
        }

        if (count($assocNotMatched) > 0) {
            foreach ($assocNotMatched as $assocForMatch => $assocForMatchValues) {
                if (false === !$assocForMatchValues['matched']) {
                    $this->addItemRelated($lsItem->getLsDoc(), $lsItem, $assocForMatch, 'all', $assocForMatchValues['type']);
                }
            }
        }
    }

    /* getHumanCodingSchemeFromDestinationNodeUri */

    /**
     * Return true or false if item has a association.
     */
    private function validatePresenceOnAssociation(string $associationValue, string $associationOnContent): bool
    {
        return $associationOnContent === $associationValue;
    }

    /**
     * Transform string content in arrays per line.
     *
     * @param string $fileContent
     */
    protected function transformContent($fileContent): array
    {
        $csvContent = str_getcsv($fileContent, "\n");
        $headers = [];
        $content = [];

        foreach ($csvContent as $i => $row) {
            $tempContent = [];
            $row = str_getcsv($row, ',');

            if (0 === $i) {
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

    protected function addItemRelated(LsDoc $lsDoc, LsItem $lsItem, string $cfAssociation, string $frameworkToAssociate, string $assocType): void
    {
        $em = $this->getEntityManager();

        if ('' !== trim($cfAssociation)) {
            if ('all' === $frameworkToAssociate) {
                $itemsAssociated = $em->getRepository(LsItem::class)
                    ->findAllByIdentifierOrHumanCodingSchemeByValue($cfAssociation);
            } else {
                $itemsAssociated = $em->getRepository(LsItem::class)
                    ->findByAllIdentifierOrHumanCodingSchemeByLsDoc($frameworkToAssociate, $cfAssociation);
            }

            if ((is_countable($itemsAssociated) ? count($itemsAssociated) : 0) > 0) {
                foreach ($itemsAssociated as $itemAssociated) {
                    $this->saveAssociation($lsDoc, $lsItem, $itemAssociated, $assocType);
                }
            } else {
                $this->saveAssociation($lsDoc, $lsItem, $cfAssociation, $assocType);
            }
        }
    }

    protected function saveAssociation(LsDoc $lsDoc, LsItem $lsItem, string|LsItem $elementAssociated, string $assocType): void
    {
        $association = new LsAssociation();
        $association->setType($assocType);
        $association->setLsDoc($lsDoc);
        $association->setOrigin($lsItem);

        if (is_string($elementAssociated)) {
            if (Uuid::isValid($elementAssociated)) {
                $association->setDestinationNodeIdentifier($elementAssociated);
            } elseif (false === !filter_var($elementAssociated, FILTER_VALIDATE_URL)) {
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

    protected function encodeHumanCodingScheme(string $humanCodingScheme): string
    {
        $prefix = 'data:text/x-ref-unresolved;base64,';

        return $prefix.base64_encode($humanCodingScheme);
    }
}
